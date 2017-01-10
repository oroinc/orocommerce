<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TaxBundle\EventListener\CustomerTaxCodeGridListener;

class CustomerTaxCodeGridListenerTest extends AbstractTaxCodeGridListenerTest
{
    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'customers-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'customers']]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityMetadataForClass')
            ->with('Oro\Bundle\TaxBundle\Entity\AbstractTaxCode')->willReturn($metadata);

        $metadata->expects($this->once())->method('getAssociationsByTargetClass')->with('\stdClass')
            ->willReturn(['stdClass' => ['fieldName' => 'customerGroups']]);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'customerGroupTaxCodes.code AS customerGroupTaxCode'
                        ],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'Oro\Bundle\TaxBundle\Entity\AbstractTaxCode',
                                    'alias' => 'customerGroupTaxCodes',
                                    'conditionType' => 'WITH',
                                    'condition' => 'customer_group MEMBER OF customerGroupTaxCodes.customerGroups'
                                ],
                            ],
                        ],
                        'from' => [['alias' => 'customers']],
                    ],
                ],
                'columns' => [
                    'customerGroupTaxCode' => ['label' => 'oro.tax.taxcode.customergroup.label', 'renderable' => false]
                ],
                'sorters' => [
                    'columns' => [
                        'customerGroupTaxCode' => ['data_name' => 'customerGroupTaxCode']
                    ]
                ],

                'filters' => [
                    'columns' => [
                        'customerGroupTaxCode' => ['data_name' => 'customerGroupTaxCode', 'type' => 'string'],
                    ]
                ],
                'name' => 'customers-grid',
            ],
            $gridConfig->toArray()
        );
    }

    /**
     * @return CustomerTaxCodeGridListener
     */
    protected function createListener()
    {
        return new CustomerTaxCodeGridListener(
            $this->doctrineHelper,
            'Oro\Bundle\TaxBundle\Entity\AbstractTaxCode',
            '\stdClass'
        );
    }
}
