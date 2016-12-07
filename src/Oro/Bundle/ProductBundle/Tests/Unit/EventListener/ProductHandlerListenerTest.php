<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener;

class ProductHandlerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductHandlerListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new ProductHandlerListener();
    }

    protected function tearDown()
    {
        unset($this->listener);
    }

    public function testClearVariantLinks()
    {
        $entity = new Product();
        $entity->setType(Product::TYPE_CONFIGURABLE);

        $productVariantLink = $this->createProductVariantLink();
        $entity->addVariantLink($productVariantLink);

        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);

        $this->assertCount(1, $entity->getVariantLinks());
        $this->assertContains($productVariantLink, $entity->getVariantLinks());

        $entity->setType(Product::TYPE_SIMPLE);
        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);
        $this->assertEmpty($entity->getVariantLinks());
    }

    /**
     * @param object $entity
     * @return AfterFormProcessEvent
     */
    protected function createEvent($entity)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock(FormInterface::class);
        return new AfterFormProcessEvent($form, $entity);
    }

    /**
     * @param Product|null $parentProduct
     * @param Product|null $product
     * @return ProductVariantLink
     */
    protected function createProductVariantLink(Product $parentProduct = null, Product $product = null)
    {
        return new ProductVariantLink($parentProduct, $product);
    }
}
