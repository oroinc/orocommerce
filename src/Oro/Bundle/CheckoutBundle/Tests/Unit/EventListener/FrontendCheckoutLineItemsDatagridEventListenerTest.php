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
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid\LineItemsDataOnResultAfterListener;
use Oro\Component\Testing\ReflectionUtil;

class FrontendCheckoutLineItemsDatagridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var LineItemShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var CheckoutLineItemsShippingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemsShippingManager;

    /** @var FrontendCheckoutLineItemsDatagridEventListener */
    private $eventListener;

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

    private function createDatagrid(array $parameters): DatagridInterface
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag($parameters));

        return $datagrid;
    }

    private function getCheckoutLineItem(
        int $id,
        string $sku,
        string $unitCode,
        string $shippingMethod,
        string $shippingMethodType
    ): CheckoutLineItem {
        $lineItem = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem, $id);
        $lineItem->setProductSku($sku);
        $lineItem->setProductUnitCode($unitCode);
        $lineItem->setShippingMethod($shippingMethod);
        $lineItem->setShippingMethodType($shippingMethodType);

        return $lineItem;
    }

    public function testOnBuildBefore()
    {
        $datagrid = $this->createDatagrid([
            'use_line_items_shipping' => true
        ]);

        $config = DatagridConfiguration::create([]);

        $this->eventListener->onBuildBefore(new BuildBefore($datagrid, $config));
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

        $config = DatagridConfiguration::create([]);

        $this->eventListener->onBuildBefore(new BuildBefore($datagrid, $config));
        $this->assertEmpty($config->offsetGetByPath('[columns][shippingMethods]'));
        $this->assertEmpty($config->offsetGetByPath('[properties]'));
    }

    public function testOnResultAfter()
    {
        $lineItem1 = $this->getCheckoutLineItem(4, 'sku-4', 'item', 'flat_rate_1', 'primary');
        $lineItem2 = $this->getCheckoutLineItem(1, 'sku-1', 'item', 'flat_rate_2', 'primary');

        $record = new ResultRecord(
            ['id' => 4, LineItemsDataOnResultAfterListener::LINE_ITEMS => [$lineItem1, $lineItem2]]
        );

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

        $this->eventListener->onResultAfter(new OrmResultAfter($datagrid, [$record]));

        $this->assertEquals($availableShippingMethods, $record->getValue('shippingMethods'));
        $this->assertEquals('flat_rate_1', $record->getValue('currentShippingMethod'));
        $this->assertEquals('primary', $record->getValue('currentShippingMethodType'));
        $this->assertEquals('sku-4:item', $record->getValue('lineItemId'));
    }

    public function testOnResultAfterIfLineItemsByIdsAreEmpty()
    {
        $lineItem1 = $this->getCheckoutLineItem(4, 'sku-4', 'item', 'flat_rate_1', 'primary');

        $record = new ResultRecord(['id' => 4]);

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

        $this->eventListener->onResultAfter(new OrmResultAfter($datagrid, [$record]));

        $this->assertEquals($availableShippingMethods, $record->getValue('shippingMethods'));
        $this->assertEquals('flat_rate_1', $record->getValue('currentShippingMethod'));
        $this->assertEquals('primary', $record->getValue('currentShippingMethodType'));
        $this->assertEquals('sku-4:item', $record->getValue('lineItemId'));
    }

    public function testOnResultAfterIfLineItemNotFound()
    {
        $record = new ResultRecord(['id' => 4]);

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

        $this->eventListener->onResultAfter(new OrmResultAfter($datagrid, [$record]));

        $this->assertNull($record->getValue('shippingMethods'));
        $this->assertNull($record->getValue('currentShippingMethod'));
        $this->assertNull($record->getValue('currentShippingMethodType'));
        $this->assertNull($record->getValue('lineItemId'));
    }

    public function testOnResultAfterIfShippingPerLineItemParameterEmpty()
    {
        $record = new ResultRecord(['id' => 4]);

        $this->managerRegistry->expects($this->never())
            ->method('getRepository');

        $this->shippingMethodProvider->expects($this->never())
            ->method('getAvailableShippingMethods');

        $datagrid = $this->createDatagrid([]);

        $this->eventListener->onResultAfter(new OrmResultAfter($datagrid, [$record]));

        $this->assertNull($record->getValue('shippingMethods'));
        $this->assertNull($record->getValue('currentShippingMethod'));
        $this->assertNull($record->getValue('currentShippingMethodType'));
        $this->assertNull($record->getValue('lineItemId'));
    }
}
