<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\EventListener\TaxCodeGridListener;

class TaxCodeGridListenerTest extends AbstractTaxCodeGridListenerTest
{
    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'customers-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'customer']]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => ['taxCodes.code AS taxCode'],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'customer.taxCode',
                                    'alias' => 'taxCodes',
                                ],
                            ],
                        ],
                        'from' => [['alias' => 'customer']],
                    ],
                ],
                'columns' => [
                    'taxCode' => [
                        'label' => 'oro.tax.taxcode.label'
                    ]
                ],
                'sorters' => ['columns' => ['taxCode' => ['data_name' => 'taxCode']]],
                'filters' => [
                    'columns' => [
                        'taxCode' => ['data_name' => 'customer.taxCode',
                            'type' => 'entity',
                            'options' => [
                                'field_options' => [
                                    'multiple' => false,
                                    'class' => AbstractTaxCode::class,
                                    'choice_label' => 'code',
                                ]
                            ]
                        ]
                    ]
                ],
                'name' => 'customers-grid',
            ],
            $gridConfig->toArray()
        );
    }

    public function testOnBuildBeforeWithoutFromPart()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A root entity is missing for grid "std-grid"');

        $gridConfig = DatagridConfiguration::create(['name' => 'std-grid']);
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->listener->onBuildBefore($event);
    }

    /**
     * @return TaxCodeGridListener
     */
    protected function createListener()
    {
        return new TaxCodeGridListener(
            'Oro\Bundle\TaxBundle\Entity\AbstractTaxCode',
            '\stdClass'
        );
    }
}
