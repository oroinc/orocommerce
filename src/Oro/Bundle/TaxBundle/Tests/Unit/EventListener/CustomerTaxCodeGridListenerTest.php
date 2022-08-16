<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\EventListener\CustomerTaxCodeGridListener;

class CustomerTaxCodeGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var CustomerTaxCodeGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new CustomerTaxCodeGridListener(AbstractTaxCode::class, $this->featureChecker);
    }

    public function testOnBuildBefore(): void
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'customers-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'customers']]);

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(AbstractTaxCode::class, 'entities')
            ->willReturn(true);

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $gridConfig);
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
                                ['join' => 'customer_group.taxCode', 'alias' => 'customerGroupTaxCodes']
                            ]
                        ],
                        'from' => [['alias' => 'customers']]
                    ]
                ],
                'columns' => [
                    'customerGroupTaxCode' => [
                        'label' => 'oro.tax.taxcode.customergroup.label',
                        'renderable' => false
                    ]
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
                                    'choice_label' => 'code'
                                ]
                            ]
                        ]
                    ]
                ],
                'name' => 'customers-grid'
            ],
            $gridConfig->toArray()
        );
    }

    public function testOnBuildBeforeWhenTaxCodeClassDisabled(): void
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'customers-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'customers']]);

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(AbstractTaxCode::class, 'entities')
            ->willReturn(false);

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $gridConfig);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'source' => [
                    'query' => [
                        'from' => [['alias' => 'customers']]
                    ]
                ],
                'name' => 'customers-grid'
            ],
            $gridConfig->toArray()
        );
    }
}
