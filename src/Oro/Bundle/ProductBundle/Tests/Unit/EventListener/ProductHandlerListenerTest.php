<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductHandlerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProductHandlerListener */
    private $listener;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->getMockBuilder(PropertyAccessor::class)
            ->onlyMethods(['setValue'])
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new ProductHandlerListener($this->propertyAccessor, $this->logger);
    }

    private function createEvent(object $entity): AfterFormProcessEvent
    {
        return new AfterFormProcessEvent($this->createMock(FormInterface::class), $entity);
    }

    public function testClearVariantLinks()
    {
        $entity = new Product();
        $entity->setType(Product::TYPE_CONFIGURABLE);

        $productVariantLink = new ProductVariantLink();
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
        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'variantFieldProperty', null);

        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);

        $this->assertNotNull($entity->notVariantFieldProperty);
    }
}
