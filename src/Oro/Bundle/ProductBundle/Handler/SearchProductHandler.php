<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Responsible for the "search" option for a product grid
 */
class SearchProductHandler
{
    /**
     * Default request search parameter key
     *
     * @var string
     */
    public const SEARCH_KEY = 'search';

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return bool|string
     */
    public function getSearchString()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $value = $request->get(self::SEARCH_KEY);

        if (!is_string($value)) {
            return false;
        }

        return trim($value);
    }
}
