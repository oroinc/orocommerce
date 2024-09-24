<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\ProductDecrementQuantityFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\View\ScrollData;

class ProductDecrementQuantityFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var ProductDecrementQuantityFormViewListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new ProductDecrementQuantityFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator,
            $this->fieldAclHelper
        );
    }

    #[\Override]
    protected function callTestMethod(): void
    {
        $this->listener->onProductView($this->event);
    }

    #[\Override]
    protected function getExpectedScrollData(): array
    {
        return [
            ScrollData::DATA_BLOCKS => [
                1 => [
                    ScrollData::TITLE => 'oro.product.sections.inventory.trans',
                    ScrollData::SUB_BLOCKS => [[]]
                ]
            ]
        ];
    }

    #[\Override]
    protected function getEntity(): object
    {
        return new Product();
    }
}
