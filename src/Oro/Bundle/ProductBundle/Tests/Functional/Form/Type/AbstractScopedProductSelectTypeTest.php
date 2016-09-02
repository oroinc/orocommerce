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
     * @var Website
     */
    protected $website;

    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                LoadCategoryProductData::class,
            ]
        );
        $this->website = $this->getContainer()->get('orob2b_website.manager')->getDefaultWebsite();

        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    protected function tearDown()
    {
        $this->configManager->reset($this->configPath, $this->website);
        $this->configManager->flush($this->website);
    }

    public function setUpBeforeRestriction()
    {
        list($availableInventoryStatuses) = func_get_args();

        $this->configManager->set($this->configPath, $availableInventoryStatuses, $this->website);
        $this->configManager->flush($this->website);
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
