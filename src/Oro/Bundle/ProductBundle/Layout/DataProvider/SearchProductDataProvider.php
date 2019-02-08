<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Converts the search text to additional data for the product page
 */
class SearchProductDataProvider
{
    /**
     * @var SearchProductHandler
     */
    private $searchProductHandler;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param SearchProductHandler $searchProductHandler
     * @param TranslatorInterface $translator
     */
    public function __construct(SearchProductHandler $searchProductHandler, TranslatorInterface $translator)
    {
        $this->searchProductHandler = $searchProductHandler;
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getSearchString(): string
    {
        return $this->searchProductHandler->getSearchString();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        $searchString = $this->getSearchString();

        return $this->translator->transChoice(
            'oro.product.search.search_title.title',
            strlen($searchString),
            ['%text%' => $searchString]
        );
    }
}
