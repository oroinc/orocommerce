<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\EventListener\TaxCodeGridListener;

class TaxCodeGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var TaxCodeGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new TaxCodeGridListener(AbstractTaxCode::class, $this->featureChecker);
    }

    public function testOnBuildBefore(): void
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'customers-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'customer']]);

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
                        'select' => ['taxCodes.code AS taxCode'],
                        'join' => [
                            'left' => [
                                ['join' => 'customer.taxCode', 'alias' => 'taxCodes']
                            ]
                        ],
                        'from' => [['alias' => 'customer']]
                    ]
                ],
                'columns' => [
                    'taxCode' => [
                        'label' => 'oro.tax.taxcode.label'
                    ]
                ],
                'sorters' => [
                    'columns' => [
                        'taxCode' => ['data_name' => 'taxCode']
                    ]
                ],
                'filters' => [
                    'columns' => [
                        'taxCode' => ['data_name' => 'customer.taxCode',
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
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'customer']]);

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
                        'from' => [['alias' => 'customer']]
                    ]
                ],
                'name' => 'customers-grid'
            ],
            $gridConfig->toArray()
        );
    }

    public function testOnBuildBeforeWithoutFromPart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A root entity is missing for grid "std-grid"');

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(AbstractTaxCode::class, 'entities')
            ->willReturn(true);

        $event = new BuildBefore(
            $this->createMock(DatagridInterface::class),
            DatagridConfiguration::create(['name' => 'std-grid'])
        );
        $this->listener->onBuildBefore($event);
    }
}
