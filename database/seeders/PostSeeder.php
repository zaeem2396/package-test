<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Seed ~75 knowledge-base style posts (policies, product help, internal docs).
     */
    public function run(): void
    {
        $topics = [
            ['refunds', 'Refunds are processed within 5–10 business days. Digital goods may be non-refundable after download. Contact support with your order id.'],
            ['shipping', 'Standard shipping takes 3–7 days. Express options available at checkout. International customs may add delays.'],
            ['privacy', 'We collect only data needed to operate the service. You may export or delete your account from settings.'],
            ['security', 'Enable two-factor authentication. Never share API keys. Report suspicious activity to security@example.com.'],
            ['billing', 'Invoices are emailed monthly. Failed payments retry twice before suspension. Update card in billing portal.'],
            ['sla', 'Enterprise tier includes 99.9% uptime SLA and priority support during business hours.'],
            ['api', 'Rate limits apply per API key. Use exponential backoff on 429 responses. Versioning is URL-based /v1.'],
        ];

        $count = 0;
        while ($count < 75) {
            foreach ($topics as [$tag, $body]) {
                if ($count >= 75) {
                    break 2;
                }
                Post::query()->create([
                    'title' => ucfirst($tag).' — '.fake()->words(3, true),
                    'content' => $body."\n\n".fake()->paragraphs(2, true),
                ]);
                $count++;
            }
        }
    }
}
