<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener;

class CheckoutGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldProvider;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var CheckoutGridListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMock('Doctrine\Common\Cache\Cache');

        $this->listener = new CheckoutGridListener($this->configProvider, $this->fieldProvider);
        $this->listener->setCache($this->cache);
    }

    public function testGetMetadataNoRelations()
    {
        $configuration = DatagridConfiguration::createNamed('test', []);
        $configuration->offsetAddToArrayByPath('[source][query][from]', [['alias' => 'rootAlias']]);
        $configuration->offsetSetByPath('[source][query][select]', ['rootAlias.id as id']);
        $configuration->offsetSetByPath('[columns]', ['id' => ['label' => 'id']]);
        $configuration->offsetSetByPath('[filters][columns]', ['id' => ['data_name' => 'id']]);
        $configuration->offsetSetByPath('[sorters][columns]', ['id' => ['data_name' => 'id']]);
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
        $relationsMetadata = [['related_entity_name' => 'Entity1', 'name' => 'relationOne']];
        $this->fieldProvider->expects($this->once())
            ->method('getRelations')
            ->with('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource')
            ->willReturn($relationsMetadata);

        $configuration = DatagridConfiguration::createNamed('test', []);
        $configuration->offsetAddToArrayByPath('[source][query][from]', [['alias' => 'rootAlias']]);
        $configuration->offsetSetByPath('[source][query][select]', ['rootAlias.id as id']);
        $configuration->offsetSetByPath('[columns]', ['id' => ['label' => 'id']]);
        $configuration->offsetSetByPath('[filters][columns]', ['id' => ['data_name' => 'id']]);
        $configuration->offsetSetByPath('[sorters][columns]', ['id' => ['data_name' => 'id']]);
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
    
    public function testGetMetadata()
    {
        $this->cache->expects($this->any())
            ->method('contains')
            ->willReturn(false);

        $relationsMetadata = [['related_entity_name' => 'Entity1', 'name' => 'relationOne']];
        $fields = [
            ['name' => 'id'],
            ['name' => 'total'],
            ['name' => 'subtotal'],
            ['name' => 'currency']
        ];

        $this->fieldProvider->expects($this->once())
            ->method('getRelations')
            ->with('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource')
            ->willReturn($relationsMetadata);
        $this->fieldProvider->expects($this->once())
            ->method('getFields')
            ->with('Entity1')
            ->willReturn($fields);

        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturn(true);
        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap(
                [
                    ['Entity1', 'id', $this->getFieldConfig([])],
                    ['Entity1', 'total', $this->getFieldConfig(['is_total' => true])],
                    ['Entity1', 'subtotal', $this->getFieldConfig(['is_subtotal' => true])],
                    ['Entity1', 'currency', $this->getFieldConfig(['is_total_currency' => true])],
                ]
            );
        
        $configuration = DatagridConfiguration::createNamed('test', []);
        $configuration->offsetAddToArrayByPath('[source][query][from]', [['alias' => 'rootAlias']]);
        $configuration->offsetSetByPath('[source][query][select]', ['rootAlias.id as id']);
        $configuration->offsetSetByPath('[columns]', ['id' => ['label' => 'id']]);
        $configuration->offsetSetByPath('[filters][columns]', ['id' => ['data_name' => 'id']]);
        $configuration->offsetSetByPath('[sorters][columns]', ['id' => ['data_name' => 'id']]);
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($datagrid, $configuration);
        $this->listener->onBuildBefore($event);

        $expectedSelects = [
            'rootAlias.id as id',
            'COALESCE(_relationOne.total) as total',
            'COALESCE(_relationOne.subtotal) as subtotal',
            'COALESCE(_relationOne.currency) as currency'
        ];
        $expectedColumns = [
            'id' => ['label' => 'id'],
            'total' => [
                'label' => 'orob2b.checkout.grid.total.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/total.html.twig'
            ],
            'subtotal' => [
                'label' => 'orob2b.checkout.grid.subtotal.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/subtotal.html.twig'
            ]
        ];
        $expectedFilters = [
            'id' => ['data_name' => 'id'],
            'total' => [
                'type' => 'number',
                'data_name' => 'total'
            ],
            'subtotal' => [
                'type' => 'number',
                'data_name' => 'subtotal'
            ]
        ];
        $expectedSorters = [
            'id' => ['data_name' => 'id'],
            'total' => ['data_name' => 'total'],
            'subtotal' => ['data_name' => 'subtotal']
        ];
        $expectedJoins = [
            ['join' => 'rootAlias.source', 'alias' => '_source'],
            ['join' => '_source.relationOne', 'alias' => '_relationOne']
        ];

        $this->assertEquals($expectedSelects, $configuration->offsetGetByPath('[source][query][select]'));
        $this->assertEquals($expectedColumns, $configuration->offsetGetByPath('[columns]'));
        $this->assertEquals($expectedFilters, $configuration->offsetGetByPath('[filters][columns]'));
        $this->assertEquals($expectedSorters, $configuration->offsetGetByPath('[sorters][columns]'));
        $this->assertEquals($expectedJoins, $configuration->offsetGetByPath('[source][query][join][left]'));
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
            ]
        ];
        $this->cache->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('test')
            ->willReturn($updates);

        $configuration = DatagridConfiguration::createNamed('test', []);
        $configuration->offsetAddToArrayByPath('[source][query][from]', [['alias' => 'rootAlias']]);
        $configuration->offsetSetByPath('[source][query][select]', ['rootAlias.id as id']);
        $configuration->offsetSetByPath('[columns]', ['id' => ['label' => 'id']]);
        $configuration->offsetSetByPath('[filters][columns]', ['id' => ['data_name' => 'id']]);
        $configuration->offsetSetByPath('[sorters][columns]', ['id' => ['data_name' => 'id']]);

        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
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

        $this->assertEquals($expectedSelects, $configuration->offsetGetByPath('[source][query][select]'));
        $this->assertEquals($expectedColumns, $configuration->offsetGetByPath('[columns]'));
        $this->assertEquals($expectedFilters, $configuration->offsetGetByPath('[filters][columns]'));
        $this->assertEquals($expectedSorters, $configuration->offsetGetByPath('[sorters][columns]'));
        $this->assertEquals($expectedJoins, $configuration->offsetGetByPath('[source][query][join][left]'));
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
