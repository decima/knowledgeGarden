<?php

namespace App\Services\Search\mailisearch;

use App\Services\Configuration\Configuration;
use App\Services\Search\SearchEngineInterface;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeiliClient implements SearchEngineInterface
{

    private Client $client;

    public function __construct(private readonly Configuration $configuration)
    {

        if (!$this->configuration->inMemory) {
            $this->client = new Client($this->configuration->searchEngine->meilisearchURL, $this->configuration->searchEngine->meilisearchToken);
        }
    }

    public static function setup(Configuration $inMemoryConfiguration)
    {
        $inMemoryConfiguration->searchEngine->meilisearchToken = $inMemoryConfiguration->searchEngine->meilisearchRootToken;
        $client = new static($inMemoryConfiguration);
        $client->createIndex();
        $token = $client->createNewToken();
        $inMemoryConfiguration->searchEngine->meilisearchToken = $token;
    }

    private function createIndex(): array
    {
        return $this->client->createIndex($this->configuration->searchEngine->meilisearchIndexName, ['primaryKey' => 'uid']);
    }

    private function createNewToken(): string
    {
        $content = $this->client->createKey([
            "actions" => [
                "search",
                "documents.*",
                "indexes.get",
            ],
            "indexes" => [$this->configuration->searchEngine->meilisearchIndexName],
            "name" => $this->configuration->title,
            "expiresAt" => null,
            "description" => "garden api key",
        ]);

        return $content->getKey();

    }

    private function getIndex(): Indexes
    {
        return $this->client->index($this->configuration->searchEngine->meilisearchIndexName);
    }

    public function index($path, $content = []): bool
    {
        $result = $this->getIndex()->updateDocuments([...$content, "path" => $path, "uid" => sha1($path)]);
        return true;
    }

    public function search($query): array
    {
        return [];

    }

    public function delete($path)
    {

    }

}