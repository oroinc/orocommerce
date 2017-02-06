<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridHelper;
use Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Model\CompletedCheckoutData;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutGridListenerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_1 = 'Entity1';
    const ENTITY_2 = 'Entity2';

    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var CheckoutGridHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutGridHelper;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyManager;

    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalProcessor;

    /**
     * @var CheckoutRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutRepository;

    /**
     * @var CheckoutGridListener
     */
    protected $listener;

    /**
     * @var EntityNameResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityNameResolver;

    protected function setUp()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')
            ->will($this->returnValueMap(
                [
                    ['currency', true],
                    ['subtotal', true],
                    ['total', true],
                ]
            ));
        $metadata->method('hasAssociation')
            ->will($this->returnValueMap(
                [
                    ['totals', true]
                ]
            ));

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getClassMetadata')->willReturn($metadata);

        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->currencyManager->expects($this->any())->method('getUserCurrency')->willReturn('USD');

        $this->cache = $this->getMockBuilder(Cache::class)->disableOriginalConstructor()->getMock();
        $this->cache->method('contains')->willReturn(false);

        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);

        $this->totalProcessor = $this->createMock(TotalProcessorProvider::class);

        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->checkoutGridHelper = $this->createMock(CheckoutGridHelper::class);

        $this->listener = new CheckoutGridListener(
            $this->currencyManager,
            $this->checkoutRepository,
            $this->totalProcessor,
            $this->entityNameResolver,
            $this->cache,
            $this->checkoutGridHelper
        );
    }

    public function testGetMetadataNoRelations()
    {
        $configuration = $this->getGridConfiguration();
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event    = new BuildBefore($datagrid, $configuration);
        $this->listener->onBuildBefore($event);

        $expectedSelects = ['rootAlias.id as id'];
        $expectedColumns = ['id' => ['label' => 'id']];
        $expectedFilters = ['id' => ['data_name' => 'id']];
        $expectedSorters = ['id' => ['data_name' => 'id']];

        $this->assertEquals($expectedSelects, $configuration->offsetGetByPath('[source][query][select]'));
        $this->assertEquals($expectedColumns, $configuration->offsetGetByPath('[columns]'));
        $this->assertEquals($expectedFilters, $configuration->offsetGetByPath('[filters][columns]'));
        $this->assertEquals($expectedSorters, $configuration->offsetGetByPath('[sorters][columns]'));
    }

    public function testGetMetadataNoTotalFields()
    {
        $configuration = $this->getGridConfiguration();
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event    = new BuildBefore($datagrid, $configuration);
        $this->listener->onBuildBefore($event);

        $expectedSelects = ['rootAlias.id as id'];
        $expectedColumns = ['id' => ['label' => 'id']];
        $expectedFilters = ['id' => ['data_name' => 'id']];
        $expectedSorters = ['id' => ['data_name' => 'id']];

        $this->assertEquals($expectedSelects, $configuration->offsetGetByPath('[source][query][select]'));
        $this->assertEquals($expectedColumns, $configuration->offsetGetByPath('[columns]'));
        $this->assertEquals($expectedFilters, $configuration->offsetGetByPath('[filters][columns]'));
        $this->assertEquals($expectedSorters, $configuration->offsetGetByPath('[sorters][columns]'));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWithValidMetadata()
    {
        $data = [
            'bindParameters' => [
                'user_currency' => 'user_currency'
            ],
            'selects' => [],
            'sorters' => [],
            'columns' => [],
            'filters' => [],
            'joins' => [],
        ];

        $this->checkoutGridHelper->expects($this->once())->method('getUpdates')->willReturn($data);

        $parameters = $this->createMock(ParameterBag::class);
        $parameters->expects($this->once())->method('set')->with(CheckoutGridListener::USER_CURRENCY_PARAMETER, 'USD');

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->method('getParameters')->willReturn($parameters);

        $config = $this->getGridConfiguration();

        $event = $this->getMockBuilder(BuildBefore::class)->disableOriginalConstructor()->getMock();
        $event->method('getConfig')->willReturn($config);
        $event->expects($this->once())->method('getDatagrid')->willReturn($datagrid);

        $this->listener->onBuildBefore($event);
    }

    /**
     * @return DatagridConfiguration
     */
    protected function getGridConfiguration()
    {
        $configuration = DatagridConfiguration::createNamed('test', []);
        $configuration->offsetAddToArrayByPath('[source][query][from]', [['alias' => 'rootAlias']]);
        $configuration->offsetSetByPath('[source][query][select]', ['rootAlias.id as id']);
        $configuration->offsetSetByPath('[columns]', ['id' => ['label' => 'id']]);
        $configuration->offsetSetByPath('[filters][columns]', ['id' => ['data_name' => 'id']]);
        $configuration->offsetSetByPath('[sorters][columns]', ['id' => ['data_name' => 'id']]);

        return $configuration;
    }

    public function testOnResultAfter()
    {
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getRecords')->will($this->returnValue([]));
        $this->listener->onResultAfter($event);
    }

    public function testBuildItemsCountColumn()
    {
        $data = array_combine(range(2, 10, 2), range(2, 10, 2));

        $this->checkoutRepository->expects($this->atLeastOnce())->method('countItemsPerCheckout')->willReturn($data);

        $records = [];
        $checkouts = [];

        for ($i = 1; $i <= 10; $i++) {
            $completed = (bool) ($i % 2);

            $records[$i] = new ResultRecord(['id' => $i, 'completed' => $completed]);

            $checkout = new Checkout();
            $checkout->getCompletedData()->offsetSet(CompletedCheckoutData::ITEMS_COUNT, $completed ? 42 + $i : null);

            $checkouts[$i] = $checkout;
        }

        $this->checkoutRepository->expects($this->any())
            ->method('find')
            ->willReturnCallback(
                function ($id) use ($checkouts) {
                    return $checkouts[$id];
                }
            );

        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->atLeastOnce())->method('getRecords')->willReturn($records);

        $this->listener->onResultAfter($event);

        foreach ($records as $key => $record) {
            $completed = (bool) ($key % 2);

            $this->assertEquals($completed ? 42 + $key : $key, $record->getValue('itemsCount'));
        }
    }

    public function testBuildStartedFromColumn()
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('test');

        $quoteDemand = new QuoteDemand();
        $quoteDemand->setQuote(new Quote());

        $this->checkoutRepository->expects($this->atLeastOnce())
            ->method('getSourcePerCheckout')
            ->willReturn([
                3 => $shoppingList,
                2 => $quoteDemand
            ]);

        $foundSources = [];
        $records = [
            new ResultRecord(['id' => 3, 'completed' => false]),
            new ResultRecord(['id' => 2, 'completed' => false]),
            new ResultRecord(['id' => 4, 'completed' => true])
        ];

        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->atLeastOnce())->method('getRecords')->willReturn($records);

        $this->entityNameResolver->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturnCallback(
                function ($entity) use ($shoppingList, $quoteDemand) {
                    if ($entity === $shoppingList) {
                        return $shoppingList->getLabel();
                    }

                    if ($entity === $quoteDemand->getQuote()) {
                        return 'Quote';
                    }

                    return null;
                }
            );

        $checkout = new Checkout();
        $checkout->getCompletedData()->offsetSet(CompletedCheckoutData::STARTED_FROM, 'started test');

        $this->checkoutRepository->expects($this->any())->method('find')->willReturn($checkout);
        $this->totalProcessor->expects($this->any())->method('getTotal')->willReturn(new Subtotal());

        $this->listener->onResultAfter($event);

        foreach ($records as $record) {
            $foundSources[] = $record->getValue('startedFrom');
        }

        $this->assertCheckoutSource($foundSources, $shoppingList->getLabel(), 'Did not found any ShoppingList entity');
        $this->assertCheckoutSource($foundSources, 'Quote', 'Did not found any Quote entity');
        $this->assertCheckoutSource($foundSources, 'started test', 'Did not found any data from completed checkout');
    }

    /**
     * @param array $sources
     * @param string $expectedLabel
     * @param string $message
     */
    protected function assertCheckoutSource(array $sources, $expectedLabel, $message)
    {
        $found = false;

        foreach ($sources as $source) {
            if ($source === $expectedLabel || (isset($source['label']) && $source['label'] === $expectedLabel)) {
                $found = true;
            }
        }

        $this->assertTrue($found, $message);
    }

    public function testBuildTotalColumn()
    {
        $this->totalProcessor->expects($this->at(0))->method('getTotal')->willReturn((new Subtotal())->setAmount(10));

        $checkout = new Checkout();
        $checkout->getCompletedData()->offsetSet(CompletedCheckoutData::TOTAL, 42);
        $checkout->getCompletedData()->offsetSet(CompletedCheckoutData::SUBTOTAL, 142);
        $checkout->getCompletedData()->offsetSet(CompletedCheckoutData::CURRENCY, 'USD');

        $this->checkoutRepository->expects($this->any())->method('find')->willReturn($checkout);
        $this->checkoutRepository->expects($this->any())
            ->method('getSourcePerCheckout')
            ->willReturn([2 => new ShoppingList()]);

        $record1 = new ResultRecord(['id' => 1, 'total' => 10, 'completed' => false]);
        $record2 = new ResultRecord(['id' => 2, 'completed' => false]);
        $record3 = new ResultRecord(['id' => 3, 'completed' => true]);

        $records = [$record1, $record2, $record3];

        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->atLeastOnce())->method('getRecords')->willReturn($records);

        $this->listener->onResultAfter($event);

        $this->assertSame(10, $record2->getValue('total'));
        $this->assertSame(42, $record3->getValue('total'));
        $this->assertSame(142, $record3->getValue('subtotal'));
        $this->assertSame('USD', $record3->getValue('currency'));
    }
}
