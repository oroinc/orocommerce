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

    /** @var int|object|null */
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
                ['availableInventoryStatuses' => ['in_stock', 'out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_4,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['in_stock', 'discontinued']],
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
                ['availableInventoryStatuses' => ['in_stock', 'out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_4,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['in_stock', 'discontinued']],
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
