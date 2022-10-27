<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductHandlerListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductHandlerListener
     */
    protected $listener;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected function setUp(): void
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new ProductHandlerListener($this->propertyAccessor, $this->logger);
    }

    protected function tearDown(): void
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

    public function testClearCustomExtendVariantFields()
    {
        $entity = new ProductStub();
        $entity->setType(Product::TYPE_CONFIGURABLE);
        $entity->variantFieldProperty = 'value';
        $entity->notVariantFieldProperty = 'value';
        $entity->setVariantFields(['variantFieldProperty']);

        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);

        $this->assertNull($entity->variantFieldProperty);
        $this->assertNotNull($entity->notVariantFieldProperty);
    }

    /**
     * @param object $entity
     * @return AfterFormProcessEvent
     */
    protected function createEvent($entity)
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
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
