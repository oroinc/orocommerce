<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\ProductHighlightLowInventoryFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductManageInventoryFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var ProductHighlightLowInventoryFormViewListener */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new ProductHighlightLowInventoryFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    protected function tearDown()
    {
        unset($this->listener);

        parent::tearDown();
    }

    /**
     * @return void
     */
    protected function callTestMethod()
    {
        $this->listener->onProductView($this->event);
    }

    /**
     * @return array
     */
    protected function getExpectedScrollData()
    {
        return ['dataBlocks' => [1 => ['title' => 'oro.product.sections.inventory.trans']]];
    }

    /**
     * @return Product
     */
    protected function getEntity()
    {
        return new Product();
    }
}
