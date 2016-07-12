<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutGridHelper;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

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
     * @var BaseCheckoutRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $baseCheckoutRepository;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

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
        $this->em = $this->getMock(EntityManagerInterface::class);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
                         ->disableOriginalConstructor()
                         ->getMock();

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

        $this->em->method('getClassMetadata')->willReturn($metadata);

        $this->currencyManager = $this->getMockBuilder(UserCurrencyManager::class)
                                      ->disableOriginalConstructor()
                                      ->getMock();

        $this->currencyManager->expects($this->any())->method('getUserCurrency')->willReturn('USD');

        $this->cache = $this->getMockBuilder(Cache::class)
                              ->disableOriginalConstructor()
                              ->getMock();

        $this->cache->method('contains')
                      ->willReturn(false);

        $this->baseCheckoutRepository = $this->getMockBuilder(
            BaseCheckoutRepository::class
        )
                                             ->setMethods(['find', 'countItemsPerCheckout', 'getSourcePerCheckout'])
                                             ->disableOriginalConstructor()
                                             ->getMock();

        $this->baseCheckoutRepository->expects($this->any())
                                     ->method('find')
                                     ->willReturn(new Checkout());

        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();

        $this->totalProcessor = $this->getMockBuilder(TotalProcessorProvider::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();

        $this->totalProcessor->expects($this->any())
                             ->method('getTotal')
                             ->willReturn((new Subtotal())->setAmount(10));

        $this->entityNameResolver = $this->getMockBuilder(EntityNameResolver::class)
                                         ->disableOriginalConstructor()
                                         ->getMock();

        $this->checkoutGridHelper = $this->getMockBuilder(CheckoutGridHelper::class)
                                         ->disableOriginalConstructor()
                                         ->getMock();

        $this->listener = new CheckoutGridListener(
            $this->currencyManager,
            $this->baseCheckoutRepository,
            $this->securityFacade,
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
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
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
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
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
            'selects'        => [],
            'sorters'        => [],
            'columns'        => [],
            'filters'        => [],
            'joins'          => [],
        ];

        $this->checkoutGridHelper->expects($this->once())
                                 ->method('getUpdates')
                                 ->willReturn($data);

        $config = $this->getGridConfiguration();

        $event = $this->getMockBuilder(BuildBefore::class)
                      ->disableOriginalConstructor()
                      ->getMock();

        $event->method('getConfig')
              ->willReturn($config);

        $parameters = $this->getMockBuilder(ParameterBag::class)
                           ->getMock();

        $parameters->expects($this->once())
            ->method('set')
            ->with(CheckoutGridListener::USER_CURRENCY_PARAMETER, 'USD');

        $datagrid = $this->getMockBuilder(DatagridInterface::class)
                         ->getMock();

        $datagrid->method('getParameters')
                 ->willReturn($parameters);

        $event->expects($this->once())
              ->method('getDatagrid')
              ->willReturn($datagrid);

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
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();

        $data = array_combine(range(1, 10), range(1, 10));

        $this->baseCheckoutRepository->expects($this->atLeastOnce())
                                     ->method('countItemsPerCheckout')
                                     ->will($this->returnValue($data));

        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $record = $this->getMock(
                'Oro\Bundle\DataGridBundle\Datasource\ResultRecord',
                ['getValue', 'addData'],
                [[]]
            );

            $record->expects($this->atLeastOnce())
                   ->method('getValue')
                   ->will($this->returnValue($i));

            $record->expects($this->atLeastOnce())
                   ->method('addData');

            $records[] = $record;
        }

        $event->expects($this->atLeastOnce())->method('getRecords')->willReturn($records);

        $event->expects($this->atLeastOnce())
              ->method('getRecords')
              ->will($this->returnValue($records));

        $this->listener->onResultAfter($event);
    }

    public function testBuildStartedFromColumn()
    {
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();

        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('test');

        $quote = new Quote();

        $this->baseCheckoutRepository->expects($this->atLeastOnce())
                                     ->method('getSourcePerCheckout')
                                     ->will($this->returnValue([
                                                                   3 => $shoppingList,
                                                                   2 => $quote
                                                               ]));

        $records = [];

        $foundSources = [];

        $record = $this->getMockBuilder('\StdClass')
                       ->setMethods(['getValue', 'addData'])
                       ->getMock();

        $record->expects($this->atLeastOnce())
               ->method('getValue')
               ->will($this->returnValue(3));

        $record->expects($this->atLeastOnce())
               ->method('addData')
               ->will($this->returnCallback(function ($value) use (& $foundSources) {
                   $foundSources[] = $value;
               }));

        $records[] = $record;

        $record = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\ResultRecord')
                       ->disableOriginalConstructor()
                       ->setMethods(['getValue', 'addData'])
                       ->getMock();

        $record->expects($this->atLeastOnce())
               ->method('getValue')
               ->will($this->returnValue(2));

        $record->expects($this->atLeastOnce())
               ->method('addData')
               ->will($this->returnCallback(function ($value) use (& $foundSources) {
                   $foundSources[] = $value;
               }));

        $records[] = $record;

        $event->expects($this->atLeastOnce())->method('getRecords')->willReturn($records);

        $event->expects($this->atLeastOnce())
              ->method('getRecords')
              ->will($this->returnValue($records));

        $this->entityNameResolver->expects($this->atLeastOnce())
             ->method('getName')
             ->will($this->returnCallback(function ($entity) use ($shoppingList, $quote) {
                if ($entity == $shoppingList) {
                    return $shoppingList->getLabel();
                }

                if ($entity == $quote) {
                    return 'Quote';
                }
             }));

        $this->listener->onResultAfter($event);

        $foundShoppingList = false;

        foreach ($foundSources as $source) {
            if ($source['startedFrom']['label'] == $shoppingList->getLabel()) {
                $foundShoppingList = true;
            }
        }

        $this->assertTrue($foundShoppingList, 'Did not found any ShoppingList entity');

        $foundQuote = false;

        foreach ($foundSources as $source) {
            if (strstr($source['startedFrom']['label'], 'Quote')) {
                $foundQuote = true;
            }
        }

        $this->assertTrue($foundQuote, 'Did not found any Quote entity');
    }

    public function testBuildTotalColumn()
    {
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();

        $this->totalProcessor->expects($this->once())
                             ->method('getTotal')
                             ->willReturn((new Subtotal())->setAmount(10));

        $this->baseCheckoutRepository->expects($this->once())
                                     ->method('find')
                                     ->with(2)
                                     ->willReturn(new Checkout());

        $record1 = new ResultRecord(['id' => 1, 'total' => 10]);
        $record2 = new ResultRecord(['id' => 2]);

        $records = [$record1, $record2];

        $event->expects($this->atLeastOnce())->method('getRecords')->willReturn($records);

        $event->expects($this->atLeastOnce())
              ->method('getRecords')
              ->will($this->returnValue($records));

        $this->listener->onResultAfter($event);

        $this->assertSame(10, $record2->getValue('total'));
    }

    /**
     * @param array $parameters
     * @return Config
     */
    protected function getFieldConfig(array $parameters)
    {
        /** @var ConfigIdInterface $configId */
        $configId = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface');

        return new Config($configId, $parameters);
    }
}
