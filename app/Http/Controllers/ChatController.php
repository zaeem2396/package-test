<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessChatNotificationJob;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\ChatJetStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use LaravelNats\Laravel\Facades\Nats;

class ChatController extends Controller
{
    public function __construct(
        private ChatJetStreamService $jetStream
    ) {}

    /** List public rooms and allow creating one. */
    public function index()
    {
        $rooms = ChatRoom::withCount('messages')
            ->where('is_private', false)
            ->orderBy('name')
            ->get();

        return view('chat.index', ['rooms' => $rooms]);
    }

    /** Show a room and its messages. */
    public function show(ChatRoom $room)
    {
        $messages = $room->messages()
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        return view('chat.room', [
            'room' => $room,
            'messages' => $messages,
        ]);
    }

    /** Store a new message: DB + publish to NATS (JetStream captures chat.room.>). */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'room_id' => 'required|exists:chat_rooms,id',
            'body' => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
        ]);

        $room = ChatRoom::findOrFail($valid['room_id']);
        $authorName = trim($valid['author_name'] ?? '') ?: 'Guest';

        $msg = ChatMessage::create([
            'chat_room_id' => $room->id,
            'user_id' => auth()->id(),
            'author_name' => $authorName,
            'body' => $valid['body'],
        ]);

        $payload = [
            'id' => $msg->id,
            'room_id' => $room->id,
            'author_name' => $msg->author_name,
            'body' => $msg->body,
            'created_at' => $msg->created_at->toIso8601String(),
        ];

        $this->jetStream->ensureStream();
        try {
            Nats::publish($room->natsSubject(), $payload);
        } catch (\Throwable) {
            // Continue: message is in DB; NATS is best-effort for fan-out
        }

        // Queue: dispatch notification job when message contains @mention (NATS queue driver)
        $mentioned = [];
        if (preg_match_all('/@(\w+)/', $valid['body'], $m)) {
            $mentioned = array_unique($m[1]);
        }
        if ($mentioned !== []) {
            ProcessChatNotificationJob::dispatch(
                $room->id,
                $room->name,
                $authorName,
                $valid['body'],
                $mentioned
            )->onConnection('nats');
        }

        $this->updatePresence($room->id, $authorName);

        if ($request->wantsJson()) {
            return response()->json($msg->only(['id', 'author_name', 'body', 'created_at']));
        }

        return redirect()
            ->route('chat.room.show', $room)
            ->with('success', 'Message sent.');
    }

    /** Poll messages for a room (for live updates). */
    public function messages(Request $request, ChatRoom $room)
    {
        $after = $request->integer('after', 0);
        $messages = $room->messages()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (ChatMessage $m) => [
                'id' => $m->id,
                'author_name' => $m->author_name,
                'body' => $m->body,
                'created_at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    /** Request/reply: who's in the room (presence). Requires chat:presence-worker running. */
    public function presence(ChatRoom $room)
    {
        try {
            $reply = Nats::request('presence.request', ['room_id' => $room->id], timeout: 2.0);
            $payload = $reply->getDecodedPayload();
            $users = $payload['users'] ?? [];
            return response()->json(['users' => $users]);
        } catch (\Throwable) {
            return response()->json(['users' => []]);
        }
    }

    /** Create a new room (simple form). */
    public function create()
    {
        return view('chat.create');
    }

    public function storeRoom(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:100',
        ]);
        $name = $valid['name'];
        $slug = Str::slug($name);
        $slug = ChatRoom::where('slug', $slug)->exists()
            ? $slug . '-' . substr(uniqid(), -4)
            : $slug;
        $room = ChatRoom::create([
            'name' => $name,
            'slug' => $slug,
            'is_private' => false,
        ]);

        return redirect()->route('chat.room.show', $room)->with('success', 'Room created.');
    }

    private function updatePresence(int $roomId, string $authorName): void
    {
        $key = 'chat_presence_room_' . $roomId;
        $users = Cache::get($key, []);
        $users[$authorName] = now()->timestamp;
        Cache::put($key, $users, now()->addMinutes(5));
    }
}
