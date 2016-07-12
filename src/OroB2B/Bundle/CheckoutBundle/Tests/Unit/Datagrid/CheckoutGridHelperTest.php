<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutGridHelper;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class CheckoutGridHelperTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_1 = 'Entity1';
    const ENTITY_2 = 'Entity2';

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldProvider;

    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    public function setUp()
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

        $this->doctrine = $this->getMockBuilder(RegistryInterface::class)
                               ->disableOriginalConstructor()
                               ->getMock();

        $this->doctrine->expects($this->any())
                       ->method('getManagerForClass')
                       ->willReturn($this->em);


        $this->fieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWithValidMetadata()
    {
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

        $testable = new CheckoutGridHelper(
            $this->doctrine,
            $this->fieldProvider,
            $this->configProvider
        );

        $gridConfig = $this->getGridConfiguration();

        $result = $testable->getUpdates($gridConfig);

        $expectedSelects = [
            'COALESCE(_relationOne.subtotal,_relationTwo_totals.subtotal) as subtotal',
            'COALESCE(_relationOne.total) as total',
            'COALESCE(_relationOne.currency,_relationTwo_totals.currency) as currency'
        ];

        $expectedColumns = [
           // 'id' => ['label' => 'id'],
            'total' => [
                'label' => 'orob2b.checkout.grid.total.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/total.html.twig',
                'order' => 85,
                'renderable' => false
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
            'subtotal' => [
                'type' => 'number',
                'data_name' => 'subtotal'
            ]
        ];

        $expectedSorters = [
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

        $this->assertEquals($expectedSelects, $result['selects']);
        $this->assertEquals($expectedColumns, $result['columns']);
        $this->assertEquals($expectedFilters, $result['filters']);
        $this->assertEquals($expectedSorters, $result['sorters']);
        $this->assertEquals($expectedJoins, $result['joins']);
        $this->assertEquals($expectedBindParameters, $result['bindParameters']);
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
