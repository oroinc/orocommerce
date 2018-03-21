<?php

namespace Oro\Bundle\WebsiteSearchBundle\QueryString;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provide information about searched string from request
 *
 * @package Oro\Bundle\WebsiteSearchBundle\QueryString
 */
class QueryStringProvider
{
    public const QUERY_PARAM = 'search';
    public const TYPE_PARAM = 'searchType';

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return string
     */
    public function getSearchQueryString(): string
    {
        return $this->getSearchString(self::QUERY_PARAM);
    }

    /**
     * @return string
     */
    public function getSearchQuerySearchType(): string
    {
        return $this->getSearchString(self::TYPE_PARAM);
    }

    /**
     * @param string $param
     *
     * @return string
     */
    protected function getSearchString(string $param): string
    {
        $currentRequest = $this->getCurrentRequest();
        $searchString   = '';

        if (null !== $currentRequest) {
            $searchString = trim($currentRequest->get($param));
        }

        return $searchString;
    }

    /**
     * @return null|Request
     */
    private function getCurrentRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
