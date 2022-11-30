<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\InventoryBundle\EventListener\CategoryHighlightLowInventoryFormViewListener;
use Oro\Bundle\UIBundle\View\ScrollData;

class CategoryHighlightLowInventoryFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryHighlightLowInventoryFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CategoryHighlightLowInventoryFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function callTestMethod(): void
    {
        $this->listener->onCategoryEdit($this->event);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedScrollData(): array
    {
        return [
            ScrollData::DATA_BLOCKS => [
                1 => [
                    ScrollData::TITLE => 'oro.catalog.sections.default_options.trans',
                    ScrollData::SUB_BLOCKS => [[]]
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntity(): object
    {
        return new Category();
    }
}
