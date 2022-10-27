<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\ProductInventoryThresholdFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\View\ScrollData;

class ProductInventoryThresholdFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var ProductInventoryThresholdFormViewListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new ProductInventoryThresholdFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    protected function tearDown(): void
    {
        unset($this->listener);

        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     */
    protected function callTestMethod()
    {
        $this->listener->onProductView($this->event);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedScrollData()
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

    /**
     * @return Product
     */
    protected function getEntity()
    {
        return new Product();
    }
}
