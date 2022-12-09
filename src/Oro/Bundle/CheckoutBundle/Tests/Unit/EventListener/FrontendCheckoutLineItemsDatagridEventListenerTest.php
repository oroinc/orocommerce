<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutLineItemRepository;
use Oro\Bundle\CheckoutBundle\EventListener\FrontendCheckoutLineItemsDatagridEventListener;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingMethodsProviderInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FrontendCheckoutLineItemsDatagridEventListenerTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $managerRegistry;
    private LineItemShippingMethodsProviderInterface|MockObject $shippingMethodProvider;
    private CheckoutLineItemsShippingManager|MockObject $lineItemsShippingManager;
    private FrontendCheckoutLineItemsDatagridEventListener $eventListener;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->shippingMethodProvider = $this->createMock(LineItemShippingMethodsProviderInterface::class);
        $this->lineItemsShippingManager = $this->createMock(CheckoutLineItemsShippingManager::class);

        $this->eventListener = new FrontendCheckoutLineItemsDatagridEventListener(
            $this->managerRegistry,
            $this->shippingMethodProvider,
            $this->lineItemsShippingManager
        );
    }

    public function testOnBuildBefore()
    {
        $datagrid = $this->createDatagrid([
            'use_line_items_shipping' => true
        ]);

        $config = $config = DatagridConfiguration::create([]);
        $event = new BuildBefore($datagrid, $config);

        $this->eventListener->onBuildBefore($event);
        $properties = $config->offsetGetByPath('[properties]');
        $this->assertNotEmpty($config->offsetGetByPath('[columns][shippingMethods]'));
        $this->assertNotEmpty($properties);
        $this->assertArrayHasKey('currentShippingMethod', $properties);
        $this->assertArrayHasKey('currentShippingMethodType', $properties);
        $this->assertArrayHasKey('lineItemId', $properties);
    }

    public function testOnBuildBeforeIfShippingPerLineItemParameterEmpty()
    {
        $datagrid = $this->createDatagrid([]);

        $config = $config = DatagridConfiguration::create([]);
        $event = new BuildBefore($datagrid, $config);

        $this->eventListener->onBuildBefore($event);
        $this->assertEmpty($config->offsetGetByPath('[columns][shippingMethods]'));
        $this->assertEmpty($config->offsetGetByPath('[properties]'));
    }

    public function testOnResultAfter()
    {
        $lineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'id' => 4,
            'productSku' => 'sku-4',
            'productUnitCode' => 'item',
            'shippingMethod' => 'flat_rate_1',
            'shippingMethodType' => 'primary',
        ]);

        $lineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'id' => 1,
            'productSku' => 'sku-1',
            'productUnitCode' => 'item',
            'shippingMethod' => 'flat_rate_2',
            'shippingMethodType' => 'primary',
        ]);

        $record = new ResultRecord([
            'id' => 4,
            'lineItemsByIds' => [
                $lineItem1,
                $lineItem2
            ]
        ]);

        $availableShippingMethods = [
            'flat_rate_1' => [
                'identifier' => 'flat_rate_1',
                'types' => [
                    'primary' => [
                        'identifier' => 'primary',
                        'label' => 'Flat Rate'
                    ]
                ]
            ],
            'fast_shipping_2' => [
                'identifier' => 'fast_shipping_2',
                'types' => [
                    'with_present' => [
                        'identifier' => 'with_present',
                        'label' => 'Fast Shipping Rate With Present'
                    ]
                ]
            ],
        ];

        $this->shippingMethodProvider->expects($this->once())
            ->method('getAvailableShippingMethods')
            ->with($lineItem1)
            ->willReturn($availableShippingMethods);

        $this->lineItemsShippingManager->expects($this->once())
            ->method('getLineItemIdentifier')
            ->willReturn('sku-4:item');

        $datagrid = $this->createDatagrid(['use_line_items_shipping' => true]);
        $event = new OrmResultAfter($datagrid, [$record]);

        $this->eventListener->onResultAfter($event);

        $this->assertEquals($availableShippingMethods, $record->getValue('shippingMethods'));
        $this->assertEquals('flat_rate_1', $record->getValue('currentShippingMethod'));
        $this->assertEquals('primary', $record->getValue('currentShippingMethodType'));
        $this->assertEquals('sku-4:item', $record->getValue('lineItemId'));
    }

    public function testOnResultAfterIfLineItemsByIdsAreEmpty()
    {
        $lineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'id' => 4,
            'productSku' => 'sku-4',
            'productUnitCode' => 'item',
            'shippingMethod' => 'flat_rate_1',
            'shippingMethodType' => 'primary',
        ]);

        $record = new ResultRecord([
            'id' => 4
        ]);

        $availableShippingMethods = [
            'flat_rate_1' => [
                'identifier' => 'flat_rate_1',
                'types' => [
                    'primary' => [
                        'identifier' => 'primary',
                        'label' => 'Flat Rate'
                    ]
                ]
            ],
            'fast_shipping_2' => [
                'identifier' => 'fast_shipping_2',
                'types' => [
                    'with_present' => [
                        'identifier' => 'with_present',
                        'label' => 'Fast Shipping Rate With Present'
                    ]
                ]
            ],
        ];

        $repository = $this->createMock(CheckoutLineItemRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn($lineItem1);

        $this->shippingMethodProvider->expects($this->once())
            ->method('getAvailableShippingMethods')
            ->with($lineItem1)
            ->willReturn($availableShippingMethods);

        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->lineItemsShippingManager->expects($this->once())
            ->method('getLineItemIdentifier')
            ->willReturn('sku-4:item');

        $datagrid = $this->createDatagrid(['use_line_items_shipping' => true]);
        $event = new OrmResultAfter($datagrid, [$record]);

        $this->eventListener->onResultAfter($event);

        $this->assertEquals($availableShippingMethods, $record->getValue('shippingMethods'));
        $this->assertEquals('flat_rate_1', $record->getValue('currentShippingMethod'));
        $this->assertEquals('primary', $record->getValue('currentShippingMethodType'));
        $this->assertEquals('sku-4:item', $record->getValue('lineItemId'));
    }

    public function testOnResultAfterIfLineItemNotFound()
    {
        $record = new ResultRecord([
            'id' => 4
        ]);

        $repository = $this->createMock(CheckoutLineItemRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->shippingMethodProvider->expects($this->never())
            ->method('getAvailableShippingMethods');

        $datagrid = $this->createDatagrid(['use_line_items_shipping' => true]);
        $event = new OrmResultAfter($datagrid, [$record]);

        $this->eventListener->onResultAfter($event);

        $this->assertNull($record->getValue('shippingMethods'));
        $this->assertNull($record->getValue('currentShippingMethod'));
        $this->assertNull($record->getValue('currentShippingMethodType'));
        $this->assertNull($record->getValue('lineItemId'));
    }

    public function testOnResultAfterIfShippingPerLineItemParameterEmpty()
    {
        $record = new ResultRecord([
            'id' => 4
        ]);

        $this->managerRegistry->expects($this->never())
            ->method('getRepository');

        $this->shippingMethodProvider->expects($this->never())
            ->method('getAvailableShippingMethods');

        $datagrid = $this->createDatagrid([]);
        $event = new OrmResultAfter($datagrid, [$record]);

        $this->eventListener->onResultAfter($event);

        $this->assertNull($record->getValue('shippingMethods'));
        $this->assertNull($record->getValue('currentShippingMethod'));
        $this->assertNull($record->getValue('currentShippingMethodType'));
        $this->assertNull($record->getValue('lineItemId'));
    }

    private function createDatagrid(array $parameters)
    {
        $parameters = new ParameterBag($parameters);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        return $datagrid;
    }
}
