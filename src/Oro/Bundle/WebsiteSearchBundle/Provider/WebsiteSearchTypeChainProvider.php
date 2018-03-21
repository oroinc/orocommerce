<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

/**
 * Store collection of route and parameters related to type
 *
 * @package Oro\Bundle\WebsiteSearchBundle\Provider
 */
class WebsiteSearchTypeChainProvider
{
    /** @var array|WebsiteSearchTypeInterface[] */
    protected $searchTypes;

    /** @var WebsiteSearchTypeInterface */
    protected $defaultSearchType;

    /**
     * SearchTypeProvider constructor.
     */
    public function __construct()
    {
        $this->searchTypes = [];
    }

    /**
     * @param string                     $type
     * @param WebsiteSearchTypeInterface $searchType
     */
    public function addSearchType(string $type, WebsiteSearchTypeInterface $searchType): void
    {
        if (!array_key_exists($type, $this->searchTypes)) {
            $this->searchTypes[$type] = $searchType;
        }
    }

    /**
     * @param WebsiteSearchTypeInterface $defaultSearchType
     */
    public function setDefaultSearchType(WebsiteSearchTypeInterface $defaultSearchType): void
    {
        $this->defaultSearchType = $defaultSearchType;
    }

    /**
     * @return WebsiteSearchTypeInterface
     */
    public function getDefaultSearchType(): WebsiteSearchTypeInterface
    {
        return $this->defaultSearchType;
    }

    /**
     * @param string $type
     *
     * @return null|WebsiteSearchTypeInterface
     */
    public function getSearchType(string $type): ?WebsiteSearchTypeInterface
    {
        if (array_key_exists($type, $this->searchTypes)) {
            return $this->searchTypes[$type];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getSearchTypes(): array
    {
        return $this->searchTypes;
    }

    /**
     * @param string $type
     *
     * @return WebsiteSearchTypeInterface
     */
    public function getSearchTypeOrDefault(string $type): WebsiteSearchTypeInterface
    {
        $searchType = $this->getSearchType($type);
        if (null !== $searchType) {
            return $searchType;
        }

        return $this->getDefaultSearchType();
    }
}
