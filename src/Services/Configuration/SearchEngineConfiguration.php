<?php

namespace App\Services\Configuration;

use Symfony\Component\Serializer\Attribute\Ignore;

class SearchEngineConfiguration
{

    public SearchEngineType $type;


    public ?string $meilisearchURL = null;
    public ?string $meilisearchToken = null;
    public ?string $meilisearchIndexName = null;

    #[ignore]
    public ?string $meilisearchRootToken = null;

    public function __construct()
    {
        $this->type = SearchEngineType::Local;
    }

    #[ignore]
    public function setSettings(string $settings)
    {
        $content = json_decode($settings);
        switch ($this->type) {
            case SearchEngineType::Meilisearch:
                $this->meilisearchURL = trim($content->meilisearchURL, "/");
                $this->meilisearchToken = $content->meilisearchToken ?? "";
                $this->meilisearchRootToken = $content->meilisearchRootToken ?? "";
                $this->meilisearchIndexName = $content->meilisearchIndexName ?? "";
                break;
        }


    }

    #[ignore]
    public function getSettings(): string
    {
        switch ($this->type) {
            case SearchEngineType::Meilisearch:
                return json_encode(["meilisearchURL" => $this->meilisearchURL, "meilisearchToken" => $this->meilisearchToken]);
        }
        return "";
    }

}