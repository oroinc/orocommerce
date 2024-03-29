<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\InventoryBundle\EventListener\CategoryQuantityToOrderFormViewListener;
use Oro\Bundle\UIBundle\View\ScrollData;

class CategoryQuantityToOrderFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryQuantityToOrderFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CategoryQuantityToOrderFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator,
            $this->fieldAclHelper
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
