<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

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

    public function testVariantLinksWithoutHasVariant()
    {
        $entity = new Product();
        $entity->setVariantFields([]);
        $entity->setHasVariants(true);
        $entity->addVariantLink($this->createProductVariantLink());
        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);
        $this->assertFalse($entity->getHasVariants());
        $this->assertCount(0, $entity->getVariantLinks());
    }

    public function testVariantLinksWithHasVariant()
    {
        $entity = new Product();
        $entity->setVariantFields([self::CUSTOM_FIELD_NAME]);
        $entity->setHasVariants(false);
        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);
        $this->assertTrue($entity->getHasVariants());
    }

    protected function createEvent($entity)
    {
        /** @var FormInterface $form */
        $form = $this->getMock('\Symfony\Component\Form\FormInterface');
        return new AfterFormProcessEvent($form, $entity);
    }

    protected function createProductVariantLink($parentProduct = null, $product = null)
    {
        return new ProductVariantLink($parentProduct, $product);
    }
}
