<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener;

class ProductHandlerListenerTest extends \PHPUnit_Framework_TestCase
{
    const CUSTOM_FIELD_NAME = 'Custom';

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
        $entity->setType(Product::TYPE_CONFIGURABLE_PRODUCT);
        $productVariantLink = $this->createProductVariantLink();
        $entity->addVariantLink($productVariantLink);
        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);
        $this->assertEquals(new ArrayCollection([$productVariantLink]), $entity->getVariantLinks());

        $entity->setType(Product::TYPE_SIMPLE_PRODUCT);
        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);
        $this->assertEquals(new ArrayCollection([]), $entity->getVariantLinks());
    }

    protected function createEvent($entity)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock(FormInterface::class);
        return new AfterFormProcessEvent($form, $entity);
    }

    protected function createProductVariantLink($parentProduct = null, $product = null)
    {
        return new ProductVariantLink($parentProduct, $product);
    }
}
