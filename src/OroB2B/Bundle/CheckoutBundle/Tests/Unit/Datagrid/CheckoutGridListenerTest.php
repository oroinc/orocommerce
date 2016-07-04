<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

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
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldProvider;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

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
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var CheckoutGridListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder(RegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->currencyManager = $this->getMockBuilder(UserCurrencyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyManager->expects($this->any())->method('getUserCurrency')->willReturn('USD');

        $this->cache = $this->getMock(Cache::class);

        $this->baseCheckoutRepository = $this->getMockBuilder(
            'OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository'
        )
            ->setMethods(['find', 'countItemsPerCheckout', 'getSourcePerCheckout'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseCheckoutRepository->expects($this->any())
            ->method('find')
            ->willReturn(new Checkout());

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnValue('Quote'));

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProcessor = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProcessor->expects($this->any())
            ->method('getTotal')
            ->willReturn((new Subtotal())->setAmount(10));

        $this->listener = new CheckoutGridListener(
            $this->configProvider,
            $this->fieldProvider,
            $this->doctrine,
            $this->currencyManager,
            $this->baseCheckoutRepository,
            $this->translator,
            $this->securityFacade,
            $this->totalProcessor
        );

        $this->listener->setCache($this->cache);
    }

    public function testGetMetadataNoRelations()
    {
        $configuration = $this->getGridConfiguration();
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($datagrid, $configuration);
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
        $relationsMetadata = [['related_entity_name' => self::ENTITY_1, 'name' => 'relationOne']];
        $this->fieldProvider->expects($this->once())
            ->method('getRelations')
            ->with('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource')
            ->willReturn($relationsMetadata);

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_1)
            ->willReturn($config);

        $configuration = $this->getGridConfiguration();
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($datagrid, $configuration);
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
        $this->cache->expects($this->any())
            ->method('contains')
            ->willReturn(false);

        $relationsMetadata = [
            ['related_entity_name' => self::ENTITY_1, 'name' => 'relationOne'],
            ['related_entity_name' => self::ENTITY_2, 'name' => 'relationTwo'],
        ];
        $this->fieldProvider->expects($this->once())
            ->method('getRelations')
            ->with('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource')
            ->willReturn($relationsMetadata);

        $configValue1 = [
            'type' => 'entity_fields',
            'fields' => [
                'total' => 'total',
                'subtotal' => 'subtotal',
                'currency' => 'currency'
            ]
        ];

        $configValue2 = [
            'type' => 'join_collection',
            'join_field' => 'totals',
            'relation_fields' => [
                'total' => 'total',
                'subtotal' => 'subtotal',
                'currency' => 'currency'
            ]
        ];

        $this->configProvider
            ->method('getConfig')
            ->will($this->returnValueMap([
                [self::ENTITY_1, null, $this->getEntityConfig($configValue1)],
                [self::ENTITY_2, null, $this->getEntityConfig($configValue2)]
            ]));

        $configuration = $this->getGridConfiguration();
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $parametersBag = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();
        $parametersBag->expects($this->once())
            ->method('set')
            ->with(CheckoutGridListener::USER_CURRENCY_PARAMETER, 'USD');
        $datagrid->expects($this->once())->method('getParameters')->willReturn($parametersBag);
        $event = new BuildBefore($datagrid, $configuration);
        $this->listener->onBuildBefore($event);

        $expectedSelects = [
            'rootAlias.id as id',
            'COALESCE(_relationOne.subtotal,_relationTwo_totals.subtotal) as subtotal',
            'COALESCE(_relationOne.total) as total',
            'COALESCE(_relationOne.currency,_relationTwo_totals.currency) as currency'
        ];
        $expectedColumns = [
            'id' => ['label' => 'id'],
            'total' => [
                'label' => 'orob2b.checkout.grid.total.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/total.html.twig',
                'order' => 85
            ],
            'subtotal' => [
                'label' => 'orob2b.checkout.grid.subtotal.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/subtotal.html.twig',
                'order' => 25
            ]
        ];
        $expectedFilters = [
            'id' => ['data_name' => 'id'],
            'subtotal' => [
                'type' => 'number',
                'data_name' => 'subtotal'
            ]
        ];
        $expectedSorters = [
            'id' => ['data_name' => 'id'],
            'subtotal' => ['data_name' => 'subtotal']
        ];
        $expectedBindParameters = ['user_currency'];
        $expectedJoins = [
            ['join' => 'rootAlias.source', 'alias' => '_source'],
            ['join' => '_source.relationOne', 'alias' => '_relationOne'],
            ['join' => '_source.relationTwo', 'alias' => '_relationTwo'],
            [
                'join' => '_relationTwo.totals',
                'alias' => '_relationTwo_totals',
                'conditionType' => 'WITH',
                'condition' => '_relationTwo_totals.currency = :user_currency'
            ],
        ];

        $this->assertEquals($expectedSelects, $configuration->offsetGetByPath('[source][query][select]'));
        $this->assertEquals($expectedColumns, $configuration->offsetGetByPath('[columns]'));
        $this->assertEquals($expectedFilters, $configuration->offsetGetByPath('[filters][columns]'));
        $this->assertEquals($expectedSorters, $configuration->offsetGetByPath('[sorters][columns]'));
        $this->assertEquals($expectedJoins, $configuration->offsetGetByPath('[source][query][join][left]'));
        $this->assertEquals($expectedBindParameters, $configuration->offsetGetByPath('[source]')['bind_parameters']);
    }

    public function testCachedData()
    {
        $updates = [
            'selects' => [
                'COALESCE(_relationOne.total) as total'
            ],
            'columns' => [
                'total' => [
                    'label' => 'orob2b.checkout.grid.total.label'
                ]
            ],
            'filters' => [
                'total' => [
                    'type' => 'number',
                    'data_name' => 'total'
                ]
            ],
            'sorters' => [
                'total' => ['data_name' => 'total']
            ],
            'joins' => [
                ['join' => 'rootAlias.source', 'alias' => '_source'],
                ['join' => '_source.relationOne', 'alias' => '_relationOne']
            ],
            'bindParameters' => ['user_currency']
        ];
        $this->cache->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('test')
            ->willReturn($updates);

        $configuration = $this->getGridConfiguration();

        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $parametersBag = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();
        $parametersBag->expects($this->once())
            ->method('set')
            ->with(CheckoutGridListener::USER_CURRENCY_PARAMETER, 'USD');
        $datagrid->expects($this->once())->method('getParameters')->willReturn($parametersBag);
        $event = new BuildBefore($datagrid, $configuration);
        $this->listener->onBuildBefore($event);

        $expectedSelects = [
            'rootAlias.id as id',
            'COALESCE(_relationOne.total) as total',
        ];
        $expectedColumns = [
            'id' => ['label' => 'id'],
            'total' => [
                'label' => 'orob2b.checkout.grid.total.label'
            ]
        ];
        $expectedFilters = [
            'id' => ['data_name' => 'id'],
            'total' => [
                'type' => 'number',
                'data_name' => 'total'
            ]
        ];
        $expectedSorters = [
            'id' => ['data_name' => 'id'],
            'total' => ['data_name' => 'total']
        ];
        $expectedJoins = [
            ['join' => 'rootAlias.source', 'alias' => '_source'],
            ['join' => '_source.relationOne', 'alias' => '_relationOne']
        ];
        $expectedBindParameters = ['user_currency'];

        $this->assertEquals($expectedSelects, $configuration->offsetGetByPath('[source][query][select]'));
        $this->assertEquals($expectedColumns, $configuration->offsetGetByPath('[columns]'));
        $this->assertEquals($expectedFilters, $configuration->offsetGetByPath('[filters][columns]'));
        $this->assertEquals($expectedSorters, $configuration->offsetGetByPath('[sorters][columns]'));
        $this->assertEquals($expectedJoins, $configuration->offsetGetByPath('[source][query][join][left]'));
        $this->assertEquals($expectedBindParameters, $configuration->offsetGetByPath('[source]')['bind_parameters']);
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

        for ($i=1; $i<=10; $i++) {
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

        $records = [ ];

        $foundSources = [ ];

        $record = $this->getMockBuilder('\StdClass')
            ->setMethods([ 'getValue', 'addData' ])
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
            ->setMethods([ 'getValue', 'addData' ])
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

        $this->translator->expects($this->atLeastOnce())
            ->method('trans')
            ->will($this->returnValue('Quote'));

        $event->expects($this->atLeastOnce())->method('getRecords')->willReturn($records);

        $event->expects($this->atLeastOnce())
            ->method('getRecords')
            ->will($this->returnValue($records));

        $this->listener->onResultAfter($event);

        $foundShoppingList = false;

        foreach ($foundSources as $source) {
            if ($source['startedFrom']['name'] == $shoppingList->getLabel()) {
                $foundShoppingList = true;
            }
        }

        $this->assertTrue($foundShoppingList, 'Did not found any ShoppingList entity');

        $foundQuote = false;

        foreach ($foundSources as $source) {
            if (strstr($source['startedFrom']['name'], 'Quote')) {
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

    /**
     * @param array $configValue
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntityConfig($configValue)
    {
        $config1 = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $config1->expects($this->once())
            ->method('has')
            ->willReturn('true');
        $config1->expects($this->once())
            ->method('get')
            ->with('totals_mapping')
            ->willReturn($configValue);

        return $config1;
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
}
