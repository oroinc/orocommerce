<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Model;

/**
 * Represents the system page content variant.
 */
class SystemPage
{
    private string $id;
    private string $url;

    public function __construct(string $id, string $url)
    {
        $this->id = $id;
        $this->url = $url;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
