<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

/**
 * Provide interface for search type object
 *
 * @package Oro\Bundle\WebsiteSearchBundle\Provider
 */
interface WebsiteSearchTypeInterface
{
    public const SEARCH_QUERY_PARAMETER = 'search';

    /**
     * @param string $searchString
     *
     * @return string
     */
    public function getRoute(string $searchString = ''): string;

    /**
     * @param string $searchString
     *
     * @return mixed
     */
    public function getRouteParameters(string $searchString = ''): array;

    /**
     * @return string
     */
    public function getLabel(): string;
}
