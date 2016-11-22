<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
abstract class AbstractScopedProductSelectTypeTest extends AbstractProductSelectTypeTest
{
    /** @var \Oro\Bundle\ConfigBundle\Config\ConfigManager */
    protected $configManager;

    /** @var string */
    protected $configPath;

    /**
     * @var int|object|null
     */
    protected $configScope;

    public function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    protected function tearDown()
    {
        $this->configManager->reset($this->configPath, $this->configScope);
        $this->configManager->flush($this->configScope);
    }

    public function setUpBeforeRestriction()
    {
        list($availableInventoryStatuses) = func_get_args();

        $this->configManager->set($this->configPath, $availableInventoryStatuses, $this->configScope);
        $this->configManager->flush($this->configScope);
    }

    /**
     * @return array
     */
    public function restrictionDataProvider()
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
                    LoadProductData::PRODUCT_8,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_4,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['in_stock', 'discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                ],
            ],
        ];
    }

    /**
     * @param string $configPath
     */
    public function setConfigPath($configPath)
    {
        $this->configPath = $configPath;
    }
}
