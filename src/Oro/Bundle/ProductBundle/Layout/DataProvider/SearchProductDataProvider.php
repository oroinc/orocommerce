<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function __construct(SearchProductHandler $searchProductHandler, TranslatorInterface $translator)
    {
        $this->searchProductHandler = $searchProductHandler;
        $this->translator = $translator;
    }

    public function getSearchString(): string
    {
        return $this->searchProductHandler->getSearchString();
    }

    public function getTitle(): string
    {
        $searchString = $this->getSearchString();

        return $this->translator->trans(
            'oro.product.search.search_title.title',
            ['%count%' => strlen($searchString), '%text%' => $searchString]
        );
    }
}
