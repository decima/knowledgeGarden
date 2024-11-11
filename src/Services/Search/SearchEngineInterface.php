<?php

namespace App\Services\Search;

interface SearchEngineInterface
{
    public function index($path, $content = []): bool;

    public function search($query): array;

    public function delete($path);
}