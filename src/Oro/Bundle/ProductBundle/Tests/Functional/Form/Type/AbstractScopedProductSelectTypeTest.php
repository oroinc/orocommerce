<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

abstract class AbstractScopedProductSelectTypeTest extends AbstractProductSelectTypeTest
{
    use ConfigManagerAwareTestTrait;

    /** @var \Oro\Bundle\ConfigBundle\Config\ConfigManager */
    protected $configManager;

    /** @var string */
    protected $configPath;

    /** @var object|int|null */
    protected $configScopeIdentifier;

    protected string $configScopeName = 'global';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configManager = self::getConfigManager($this->configScopeName);
    }

    public function setUpBeforeRestriction()
    {
        [$availableInventoryStatuses] = func_get_args();

        $this->configManager->set($this->configPath, $availableInventoryStatuses, $this->configScopeIdentifier);
        $this->configManager->flush($this->configScopeIdentifier);
    }

    /**
     * {@inheritDoc}
     */
    public function restrictionSelectDataProvider(): array
    {
        return [
            [
                [
                    'availableInventoryStatuses' => [
                        'prod_inventory_status.in_stock',
                        'prod_inventory_status.out_of_stock'
                    ]
                ],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_4,
                ]
            ],
            [
                [
                    'availableInventoryStatuses' => [
                        'prod_inventory_status.in_stock',
                        'prod_inventory_status.discontinued'
                    ]
                ],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_6,
                ]
            ]
        ];
    }

    public function restrictionGridDataProvider(): array
    {
        return [
            [
                [
                    'availableInventoryStatuses' => [
                        'prod_inventory_status.in_stock',
                        'prod_inventory_status.out_of_stock'
                    ]
                ],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_4,
                ]
            ],
            [
                [
                    'availableInventoryStatuses' => [
                        'prod_inventory_status.in_stock',
                        'prod_inventory_status.discontinued'
                    ]
                ],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ],
            ]
        ];
    }

    public function setConfigPath(string $configPath): void
    {
        $this->configPath = $configPath;
    }
}
