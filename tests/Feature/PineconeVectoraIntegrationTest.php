<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;
use Vectora\Pinecone\Laravel\Facades\Pinecone;

#[Group('pinecone')]
#[Group('pinecone-poc')]
class PineconeVectoraIntegrationTest extends TestCase
{
    private const POC_PREFIX = 'vectora-phpunit';

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->pineconeDataPlaneConfigured()) {
            $this->markTestSkipped(
                'Set PINECONE_API_KEY and PINECONE_HOST (or publish config + .env) to run live Pinecone tests.'
            );
        }
    }

    public function test_describe_stats_upsert_query_and_delete_round_trip(): void
    {
        $store = Pinecone::connection();
        $stats = $store->describeIndexStats();
        $this->assertGreaterThan(0, $stats->dimension);

        $dim = $stats->dimension;
        $ids = [self::POC_PREFIX.'-a', self::POC_PREFIX.'-b'];

        $vectors = [
            new VectorRecord(
                id: $ids[0],
                values: $this->normalizedDummyVector($dim, 11),
                metadata: ['test' => 'phpunit'],
            ),
            new VectorRecord(
                id: $ids[1],
                values: $this->normalizedDummyVector($dim, 22),
                metadata: ['test' => 'phpunit'],
            ),
        ];

        $upsert = $store->upsert(new UpsertVectorsRequest($vectors));
        $this->assertGreaterThanOrEqual(1, $upsert->upsertedCount);

        $filter = ['test' => ['$eq' => 'phpunit']];
        $result = $store->query(new QueryVectorsRequest(
            vector: $vectors[0]->values,
            topK: 10,
            filter: $filter,
        ));
        $this->assertNotEmpty($result->matches);
        $selfMatch = null;
        foreach ($result->matches as $m) {
            if ($m->id === $ids[0]) {
                $selfMatch = $m;
                break;
            }
        }
        $this->assertNotNull($selfMatch, 'Filtered query should return the upserted id (metadata test=phpunit).');
        $this->assertGreaterThan(0.9, $selfMatch->score);

        $store->delete(new DeleteVectorsRequest(ids: $ids));
    }

    private function pineconeDataPlaneConfigured(): bool
    {
        $apiKey = (string) config('pinecone.api_key', '');
        if ($apiKey === '') {
            return false;
        }

        $config = config('pinecone', []);
        $defaultName = (string) ($config['default'] ?? 'default');
        $indexes = $config['indexes'] ?? [];
        $host = '';
        if (is_array($indexes) && isset($indexes[$defaultName]) && is_array($indexes[$defaultName])) {
            $host = (string) ($indexes[$defaultName]['host'] ?? '');
        }
        if ($host === '' && ($config['host'] ?? '') !== '') {
            $host = (string) $config['host'];
        }

        return $host !== '';
    }

    /**
     * @return list<float>
     */
    private function normalizedDummyVector(int $dimension, int $seed): array
    {
        $values = [];
        for ($i = 0; $i < $dimension; $i++) {
            $values[] = sin(($i + 1) * ($seed * 0.731) + $seed);
        }
        $sum = 0.0;
        foreach ($values as $x) {
            $sum += $x * $x;
        }
        $norm = sqrt($sum);
        if ($norm < 1e-12) {
            return array_fill(0, $dimension, 0.0);
        }

        return array_map(static fn (float $x): float => $x / $norm, $values);
    }
}
