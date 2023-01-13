<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Datagrid\FrontendLineItemsGridExtension;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutLineItemRepository;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
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

    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var ParameterBag */
    private $parameters;

    /** @var FrontendLineItemsGridExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->lineItemRepository = $this->createMock(CheckoutLineItemRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [Checkout::class, $this->checkoutRepository],
                    [CheckoutLineItem::class, $this->lineItemRepository]
                ]
            );

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);

        $this->parameters = new ParameterBag();

        $this->extension = new FrontendLineItemsGridExtension(
            $registry,
            $this->configManager,
            $this->checkoutLineItemsManager
        );
        $this->extension->setParameters($this->parameters);
    }

    public function testIsApplicable(): void
    {
        $this->assertTrue(
            $this->extension->isApplicable(
                DatagridConfiguration::create(['name' => 'frontend-checkout-line-items-grid'])
            )
        );
        $this->assertTrue(
            $this->extension->isApplicable(
                DatagridConfiguration::create(['name' => 'frontend-single-page-checkout-line-items-grid'])
            )
        );
    }

    public function testIsNotApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'checkout-line-items-grid']);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testSetParameters(): void
    {
        $this->extension->setParameters(
            new ParameterBag(
                [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        'g' => [
                            'group' => true,
                        ],
                    ],
                ],
            )
        );

        $this->assertEquals(
            [
                ParameterBag::MINIFIED_PARAMETERS => [
                    'g' => [
                        'group' => true,
                    ],
                ],
                ParameterBag::ADDITIONAL_PARAMETERS => [
                    'group' => true,
                ],
            ],
            $this->extension->getParameters()->all()
        );
    }

    public function testSetParametersWithoutGroup(): void
    {
        $this->extension->setParameters(new ParameterBag());

        $this->assertEquals([], $this->extension->getParameters()->all());
    }

    public function testProcessConfigs(): void
    {
        $this->parameters->set('checkout_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page', false, false, null)
            ->willReturn(1000);

        $checkout = $this->createCheckout(900);

        $this->checkoutRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($checkout);

        $converter = new CheckoutLineItemsConverter();

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($converter->convert($checkout->getLineItems()->toArray()));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                [
                                    'label' => 'oro.checkout.grid.toolbar.pageSize.all.label',
                                    'size' => 1000
                                ]
                            ],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                        ],
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithoutId(): void
    {
        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->checkoutRepository->expects($this->never())
            ->method('find');

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                        ],
                    ],
                ],
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
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page', false, false, null)
            ->willReturn(1000);

        $checkout = $this->createCheckout(2000);

        $this->checkoutRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($checkout);

        $converter = new CheckoutLineItemsConverter();

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($converter->convert($checkout->getLineItems()->toArray()));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100, 1000],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                        ],
                    ],
                ],
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
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page', false, false, null)
            ->willReturn(1000);

        $checkout = $this->createCheckout(999);

        $this->checkoutRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($checkout);

        $converter = new CheckoutLineItemsConverter();

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($converter->convert($checkout->getLineItems()->toArray()));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                [
                                    'label' => 'oro.checkout.grid.toolbar.pageSize.all.label',
                                    'size' => 1000
                                ]
                            ],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                        ],
                    ],
                ],
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
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->never())
            ->method('get');

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
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
                            'AND innerItem.productUnit = lineItem.productUnit) as allLineItemsIds',
                        ],
                    ],
                ],
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
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_checkout.checkout_max_line_items_per_page', false, false, null)
            ->willReturn(1000);

        $checkout = $this->createCheckout(10);

        $this->checkoutRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($checkout);

        $converter = new CheckoutLineItemsConverter();

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($converter->convert($checkout->getLineItems()->toArray()));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                [
                                    'label' => 'oro.checkout.grid.toolbar.pageSize.all.label',
                                    'size' => 1000
                                ]
                            ],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
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
                ],
            ],
            $config->toArray()
        );
    }

    public function testVisitMetadata(): void
    {
        $this->parameters->set('checkout_id', 42);

        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects($this->once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        $this->assertEquals(
            [
                'canBeGrouped' => true,
                'initialState' => [
                    'parameters' => [
                        'group' => false,
                    ],
                ],
                'state' => [
                    'parameters' => [
                        'group' => false,
                    ],
                ],
            ],
            $data->toArray()
        );
    }

    public function testVisitMetadataWithoutId(): void
    {
        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects($this->never())
            ->method('canBeGrouped');

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        $this->assertEquals([], $data->toArray());
    }

    public function testVisitResult(): void
    {
        $this->parameters->set('checkout_id', 42);

        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects($this->once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        $this->assertTrue($data->offsetGetByPath('[metadata][canBeGrouped]'));
    }

    public function testVisitResultWithoutId(): void
    {
        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects($this->never())
            ->method('canBeGrouped');

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        $this->assertEquals([], $data->toArray());
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
