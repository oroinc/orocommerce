<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Datagrid\FrontendLineItemsGridExtension;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutLineItemRepository;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FrontendLineItemsGridExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutRepository;

    /** @var CheckoutLineItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemRepository;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsProvider;

    /** @var ParameterBag */
    private $parameters;

    /** @var FrontendLineItemsGridExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->lineItemRepository = $this->createMock(CheckoutLineItemRepository::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->parameters = new ParameterBag();

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [Checkout::class, null, $this->checkoutRepository],
                [CheckoutLineItem::class, null, $this->lineItemRepository]
            ]);

        $this->extension = new FrontendLineItemsGridExtension(
            ['frontend-checkout-line-items-grid', 'frontend-single-page-checkout-line-items-grid'],
            $doctrine,
            $this->configManager,
            $this->checkoutLineItemsProvider
        );
        $this->extension->setParameters($this->parameters);
    }

    public function testIsApplicable(): void
    {
        self::assertTrue(
            $this->extension->isApplicable(
                DatagridConfiguration::create(['name' => 'frontend-checkout-line-items-grid'])
            )
        );
        self::assertTrue(
            $this->extension->isApplicable(
                DatagridConfiguration::create(['name' => 'frontend-single-page-checkout-line-items-grid'])
            )
        );
    }

    public function testIsNotApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'checkout-line-items-grid']);

        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testSetParameters(): void
    {
        $this->extension->setParameters(
            new ParameterBag([ParameterBag::MINIFIED_PARAMETERS => ['g' => ['group' => true]]])
        );

        self::assertEquals(
            [
                ParameterBag::MINIFIED_PARAMETERS => ['g' => ['group' => true]],
                ParameterBag::ADDITIONAL_PARAMETERS => ['group' => true]
            ],
            $this->extension->getParameters()->all()
        );
    }

    public function testSetParametersWithoutGroup(): void
    {
        $this->extension->setParameters(new ParameterBag());

        self::assertEquals([], $this->extension->getParameters()->all());
    }

    public function testProcessConfigs(): void
    {
        $this->parameters->set('checkout_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ]
            ]
        );

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page')
            ->willReturn(1000);

        $checkout = $this->createCheckout(900);

        $this->checkoutRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($checkout);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($checkout->getLineItems());

        $this->extension->processConfigs($config);

        self::assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                ['label' => 'oro.checkout.grid.toolbar.pageSize.all.label', 'size' => 1000]
                            ]
                        ]
                    ]
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id'
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWhenNoCheckoutId(): void
    {
        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ]
            ]
        );

        $this->checkoutRepository->expects(self::never())
            ->method('find');

        $this->extension->processConfigs($config);

        self::assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id'
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWhenCheckoutNotFound(): void
    {
        $this->parameters->set('checkout_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ]
            ]
        );

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page')
            ->willReturn(1000);

        $this->checkoutRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn(null);

        $this->extension->processConfigs($config);

        self::assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                ['label' => 'oro.checkout.grid.toolbar.pageSize.all.label', 'size' => 1000]
                            ]
                        ]
                    ]
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id'
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsCountMoreThanConfig(): void
    {
        $this->parameters->set('checkout_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ]
            ]
        );

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page')
            ->willReturn(1000);

        $checkout = $this->createCheckout(2000);

        $this->checkoutRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($checkout);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($checkout->getLineItems());

        $this->extension->processConfigs($config);

        self::assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100, 1000]
                        ]
                    ]
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id'
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsCountLessThanConfig(): void
    {
        $this->parameters->set('checkout_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ]
            ]
        );

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page')
            ->willReturn(1000);

        $checkout = $this->createCheckout(999);

        $this->checkoutRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($checkout);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($checkout->getLineItems());

        $this->extension->processConfigs($config);

        self::assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                ['label' => 'oro.checkout.grid.toolbar.pageSize.all.label', 'size' => 1000]
                            ]
                        ]
                    ]
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id'
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithGrouping(): void
    {
        $this->parameters->set('_parameters', ['group' => true]);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ]
            ]
        );

        $this->configManager->expects(self::never())
            ->method('get');

        $this->extension->processConfigs($config);

        self::assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT GROUP_CONCAT(innerItem.id ORDER BY innerItem.id ASC) ' .
                            'FROM Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem innerItem ' .
                            'WHERE innerItem.id NOT IN (:unacceptable_ids) ' .
                            'AND (innerItem.parentProduct = lineItem.parentProduct ' .
                            'OR innerItem.product = lineItem.product) ' .
                            'AND innerItem.checkout = lineItem.checkout ' .
                            'AND innerItem.productUnit = lineItem.productUnit) as allLineItemsIds'
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithAcceptableIds(): void
    {
        $this->parameters->set('checkout_id', 42);
        $this->parameters->set('acceptable_ids', [2, 3, 5]);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100]
                        ]
                    ]
                ]
            ]
        );

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page')
            ->willReturn(1000);

        $checkout = $this->createCheckout(10);

        $this->checkoutRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($checkout);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($checkout->getLineItems());

        $this->extension->processConfigs($config);

        self::assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                ['label' => 'oro.checkout.grid.toolbar.pageSize.all.label', 'size' => 1000]
                            ]
                        ]
                    ]
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id'
                        ],
                        'where' => [
                            'and' => [
                                'lineItem.id IN (:acceptable_ids)'
                            ]
                        ]
                    ],
                    'bind_parameters' => [
                        'acceptable_ids'
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testVisitMetadata(): void
    {
        $this->parameters->set('checkout_id', 42);

        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects(self::once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        self::assertEquals(
            [
                'canBeGrouped' => true,
                'initialState' => ['parameters' => ['group' => false]],
                'state' => ['parameters' => ['group' => false]]
            ],
            $data->toArray()
        );
    }

    public function testVisitMetadataWithoutId(): void
    {
        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects(self::never())
            ->method('canBeGrouped');

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        self::assertEquals([], $data->toArray());
    }

    public function testVisitResult(): void
    {
        $this->parameters->set('checkout_id', 42);

        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects(self::once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        self::assertTrue($data->offsetGetByPath('[metadata][canBeGrouped]'));
    }

    public function testVisitResultWithoutId(): void
    {
        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects(self::never())
            ->method('canBeGrouped');

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        self::assertEquals([], $data->toArray());
    }

    private function createCheckout(int $lineItemsCount): Checkout
    {
        $checkout = new Checkout();
        for ($i = 0; $i < $lineItemsCount; $i++) {
            $checkout->addLineItem(new CheckoutLineItem());
        }

        return $checkout;
    }
}
