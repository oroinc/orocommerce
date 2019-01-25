<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
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

    /**
     * @var HtmlTagHelper
     */
    private $htmlTagHelper;

    /**
     * @param RequestStack $requestStack
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function __construct(RequestStack $requestStack, HtmlTagHelper $htmlTagHelper)
    {
        $this->requestStack = $requestStack;
        $this->htmlTagHelper = $htmlTagHelper;
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

        $value = $this->htmlTagHelper->escape($value);

        return trim($value);
    }
}
