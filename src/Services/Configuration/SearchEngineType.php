<?php

namespace App\Services\Configuration;

enum SearchEngineType: string
{
    case None = 'none';
    case Local = 'local';
    case Meilisearch = 'meilisearch';

}