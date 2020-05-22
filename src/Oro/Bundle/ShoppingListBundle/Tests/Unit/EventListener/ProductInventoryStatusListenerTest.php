<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductWithInventoryStatus;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\ShoppingListBundle\Async\Topics;
use Oro\Bundle\ShoppingListBundle\EventListener\ProductInventoryStatusListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductInventoryStatusListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var MessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteProvider;

    /**
     * @var ProductInventoryStatusListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);

        $this->listener = new ProductInventoryStatusListener(
            $this->configManager,
            $this->messageFactory,
            $this->producer,
            $this->websiteProvider
        );
    }

    public function testPreUpdateInventoryStatusUnchanged()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $args */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('inventory_status')
            ->willReturn(false);

        $this->producer->expects($this->never())
            ->method($this->anything());

        $this->listener->preUpdate($product, $args);
    }

    public function testPreUpdate()
    {
        /** @var AbstractEnumValue|\PHPUnit\Framework\MockObject\MockObject $inventoryStatus */
        $inventoryStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $inventoryStatus->expects($this->any())
            ->method('getId')
            ->willReturn('out_of_stock');
        /** @var Product $product */
        $product = $this->getEntity(ProductWithInventoryStatus::class, ['id' => 1]);
        $product->setInventoryStatus($inventoryStatus);
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $args */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('inventory_status')
            ->willReturn(true);

        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website3 = $this->getEntity(Website::class, ['id' => 3]);
        $websites = [
            1 => $website1,
            3 => $website3
        ];
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);
        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with(
                'oro_product.general_frontend_product_visibility',
                $websites
            )
            ->willReturn([
                1 => ['in_stock', 'out_of_stock'],
                3 => ['in_stock']
            ]);

        $data = [
            'products' => [1],
            'context' => [
                'class' => Website::class,
                'id' => 3
            ]
        ];
        $this->messageFactory->expects($this->once())
            ->method('createShoppingTotalsInvalidateMessage')
            ->with($website3, [$product->getId()])
            ->willReturn($data);
        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_PRODUCT,
                $data
            );

        $this->listener->preUpdate($product, $args);
    }

    public function testPreUpdateWithZeroContext()
    {
        /** @var AbstractEnumValue|\PHPUnit\Framework\MockObject\MockObject $inventoryStatus */
        $inventoryStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $inventoryStatus->expects($this->any())
            ->method('getId')
            ->willReturn('out_of_stock');
        /** @var Product $product */
        $product = $this->getEntity(ProductWithInventoryStatus::class, ['id' => 1]);
        $product->setInventoryStatus($inventoryStatus);
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $args */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('inventory_status')
            ->willReturn(true);

        $website = $this->getEntity(Website::class, ['id' => 1]);
        $websites = [
            1 => $website
        ];
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);
        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with(
                'oro_product.general_frontend_product_visibility',
                $websites
            )
            ->willReturn([
                0 => ['in_stock']
            ]);

        $data = [
            'products' => [1]
        ];
        $this->messageFactory->expects($this->once())
            ->method('createShoppingTotalsInvalidateMessage')
            ->with(null, [$product->getId()])
            ->willReturn($data);
        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_PRODUCT,
                $data
            );

        $this->listener->preUpdate($product, $args);
    }
}
