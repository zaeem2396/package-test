<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Deliberately triggers runtime errors so Runtime Insight (clarityphp/runtime-insight)
 * can detect and explain them. Each route triggers one of the supported error types.
 * Static analysis will flag these on purpose (undefined class, wrong args, etc.).
 *
 * @see https://github.com/clarityphp/runtime-insight
 */
class RuntimeInsightTestController extends Controller
{
    public function index(): View
    {
        return view('runtime-insight.index');
    }

    /**
     * Null pointer: call a method on null.
     * Triggers: "Call to a member function ... on null"
     */
    public function nullPointer(): never
    {
        $user = null;
        $user->getId(); // deliberate: call on null
    }

    /**
     * Undefined array key.
     * Triggers: "Undefined array key ..."
     */
    public function undefinedIndex(): never
    {
        $data = ['name' => 'test'];
        $value = $data['user_id']; // deliberate: missing key
    }

    /**
     * Type error: wrong type passed to a function.
     * Triggers: "Argument #1 must be of type string, int given"
     */
    public function typeError(): never
    {
        $this->requireString(123); // deliberate: int instead of string
    }

    /**
     * Argument count: too few arguments.
     * Triggers: "Too few arguments to function ..."
     */
    public function argumentCount(): never
    {
        $this->twoArgs(1); // deliberate: only one arg, two required
    }

    /**
     * Class not found: reference a non-existent class.
     * Triggers: "Class '...' not found"
     */
    public function classNotFound(): never
    {
        new \App\NonExistentModel(); // deliberate: class does not exist
    }

    private function requireString(string $value): string
    {
        return $value;
    }

    private function twoArgs(int $a, int $b): int
    {
        return $a + $b;
    }
}
