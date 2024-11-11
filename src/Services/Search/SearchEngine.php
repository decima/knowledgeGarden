<?php

namespace App\Services\Search;

use App\Services\Configuration\Configuration;
use App\Services\Configuration\SearchEngineType;
use App\Services\Search\mailisearch\MeiliClient;

class SearchEngine
{

    public function __construct(
        private Configuration $configuration,
        private MeiliClient   $meiliClient,
    )
    {

    }

    private function getClient(): SearchEngineInterface
    {
        switch ($this->configuration->searchEngine->type) {
            case SearchEngineType::Meilisearch:
                return $this->meiliClient;
        }

        throw new \Exception("no client defined");
    }


    public function index($path, $fileName, $document, $metadata = []): bool
    {
        return $this->getClient()->index($path, ["filename" => $fileName,
            "content" => $document, "metadata" => $metadata]);

    }
}