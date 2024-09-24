<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\InventoryBundle\EventListener\CategoryQuantityToOrderFormViewListener;
use Oro\Bundle\UIBundle\View\ScrollData;

class CategoryQuantityToOrderFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryQuantityToOrderFormViewListener */
    private $listener;

    #[\Override]
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

    #[\Override]
    protected function callTestMethod(): void
    {
        $this->listener->onCategoryEdit($this->event);
    }

    #[\Override]
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

    #[\Override]
    protected function getEntity(): object
    {
        return new Category();
    }
}
