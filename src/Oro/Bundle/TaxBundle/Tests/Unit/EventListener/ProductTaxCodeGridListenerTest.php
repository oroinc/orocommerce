<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\EventListener\ProductTaxCodeGridListener;

class ProductTaxCodeGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ProductTaxCodeGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ProductTaxCodeGridListener(AbstractTaxCode::class, $this->featureChecker);
    }

    public function testOnBuildBefore(): void
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'products-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'product']]);

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
                                ['join' => 'product.taxCode', 'alias' => 'taxCodes']
                            ]
                        ],
                        'from' => [['alias' => 'product']]
                    ]
                ],
                'columns' => [
                    'taxCode' => [
                        'label' => 'oro.tax.taxcode.label',
                        'renderable' => false,
                        'inline_editing' => [
                            'enable' => true,
                            'editor' => [
                                'view' => 'orotax/js/app/views/editor/product-tax-code-editor-view',
                                'view_options' => [
                                    'value_field_name' => 'taxCode'
                                ]
                            ],
                            'autocomplete_api_accessor' => [
                                'class' => 'oroui/js/tools/search-api-accessor',
                                'label_field_name' => 'code',
                                'search_handler_name' => 'oro_product_tax_code'
                            ],
                            'save_api_accessor' => [
                                'route' => 'oro_api_patch_product_tax_code',
                                'query_parameter_names' => ['id']
                            ]
                        ]
                    ]
                ],
                'sorters' => [
                    'columns' => [
                        'taxCode' => ['data_name' => 'taxCode']
                    ]
                ],
                'filters' => [
                    'columns' => [
                        'taxCode' => [
                            'data_name' => 'product.taxCode',
                            'type' => 'entity',
                            'options' => [
                                'field_options' => [
                                    'multiple' => false,
                                    'class' => AbstractTaxCode::class,
                                    'choice_label' => 'code'
                                ]
                            ],
                            'renderable' => false
                        ]
                    ]
                ],
                'name' => 'products-grid'
            ],
            $gridConfig->toArray()
        );
    }

    public function testOnBuildBeforeWhenTaxCodeClassDisabled(): void
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'products-grid']);
        $gridConfig->offsetSetByPath('[source][query][from]', [['alias' => 'product']]);

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
                        'from' => [['alias' => 'product']]
                    ]
                ],
                'name' => 'products-grid'
            ],
            $gridConfig->toArray()
        );
    }
}
