<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductLineItemListener;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\PreviouslyPurchasedOrderStatusesProviderStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

class ReindexProductLineItemListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var ProductReindexManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $reindexManager;

    /** @var ReindexProductLineItemListener */
    protected $listener;

    /** @var OrderLineItem|\PHPUnit_Framework_MockObject_MockObject */
    protected $lineItem;

    /** @var  Order */
    protected $order;

    /** @var FeatureChecker  */
    protected $featureChecker;

    /** @var  Website */
    protected $website;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->event = $this->createMock(PreUpdateEventArgs::class);
        $this->reindexManager = $this->createMock(ProductReindexManager::class);
        $statusProvider = new PreviouslyPurchasedOrderStatusesProviderStub();
        $this->lineItem = $this->createMock(OrderLineItem::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->website = $this->createMock(Website::class);
        $this->website->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->order = $this->getEntity(OrderStub::class);
        $this->order->setInternalStatus(new StubEnumValue('closed', 'closed'));
        $this->order->setWebsite($this->website);

        $this->lineItem->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->listener = new ReindexProductLineItemListener(
            $this->reindexManager,
            $statusProvider
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('previously_purchased_products');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->order);
        unset($this->listener);
        unset($this->lineItem);
        unset($this->reindexManager);
        unset($this->event);
        unset($this->featureChecker);
    }

    public function testOrderLineItemProductNotChanged()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD)
            ->willReturn(false);

        $this->reindexManager->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
    }

    public function testOrderLineItemProductChange()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD)
            ->willReturn(true);
        $product = $this->createMock(Product::class);
        $this->event->expects($this->once())
            ->method('getOldValue')
            ->willReturn($product);
        $this->event->expects($this->once())
            ->method('getNewValue')
            ->willReturn($product);

        $this->reindexManager->expects($this->exactly(2))
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
    }

    public function testReindexProductOnLineItemCreateOrDelete()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);
        $product = $this->createMock(Product::class);
        $this->lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->reindexManager->expects($this->once())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem, $this->event);
    }

    public function testReindexInOrderWithUnavailableStatus()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);
        $this->order->setInternalStatus(new StubEnumValue('open', 'open'));
        $this->setupAssertForCaseFieldChanged();
        $this->reindexManager->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
    }

    public function testReindexInOrderWithInvalidWebsite()
    {
        $this->setupAssertForCaseFieldChanged();
        $reflection = new \ReflectionClass($this->order);
        $websiteProperty = $reflection->getProperty('website');
        $websiteProperty->setAccessible(true);
        $websiteProperty->setValue($this->order, null);

        $this->reindexManager->expects($this->never())
            ->method('reindexProduct');
        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
    }

    public function testFeatureDisabled()
    {
        $this->featureChecker->expects($this->exactly(2))
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);

        $this->reindexManager->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem, $this->event);
    }

    protected function setupAssertForCaseFieldChanged()
    {
        $this->event->expects($this->any())
            ->method('hasChangedField')
            ->with(ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD)
            ->willReturn(true);
        $product = $this->createMock(Product::class);
        $this->event->expects($this->any())
            ->method('getOldValue')
            ->willReturn($product);
        $this->event->expects($this->any())
            ->method('getNewValue')
            ->willReturn($product);
    }
}
