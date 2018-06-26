<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\EventListener\CustomerTaxCodeGridListener;

class CustomerTaxCodeGridListenerTest extends AbstractTaxCodeGridListenerTest
{
    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'customers-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'customers']]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

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
                                    'join' => 'customer_group.taxCode',
                                    'alias' => 'customerGroupTaxCodes',
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

                        'customerGroupTaxCode' => [
                            'data_name' => 'customer_group.taxCode',
                            'type' => 'entity',
                            'options' => [
                                'field_options' => [
                                    'multiple' => false,
                                    'class' => AbstractTaxCode::class,
                                    'choice_label' => 'code',
                                ]
                            ],
                        ]
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
            'Oro\Bundle\TaxBundle\Entity\AbstractTaxCode',
            '\stdClass'
        );
    }
}
