<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Model;

/**
 * The model for the system page content variant API resource.
 */
class SystemPage
{
    /** @var string */
    private $id;

    /** @var string */
    private $url;

    /**
     * @param string $id
     * @param string $url
     */
    public function __construct(string $id, string $url)
    {
        $this->id = $id;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
