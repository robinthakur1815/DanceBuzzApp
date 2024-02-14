<?php

return [
    'host' => env('ELASTICSEARCH_HOST'),
    'indices' => [
        'mappings' => [
            'properties' => [
                // "title" => ["type" => "text", "fields" => ["raw" => ["type" => "keywords"]]],
                // "published_content" => ["type" => "text", "fields" => ["raw" => ["type" => "keywords"]]]
                'title' => ['type' => 'text'],
            ],
        ],
        'settings' => [
            'default' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ],
        ],
    ],
];
