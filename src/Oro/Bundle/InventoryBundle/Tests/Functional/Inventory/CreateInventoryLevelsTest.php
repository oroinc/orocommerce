<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Inventory;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\Controller\ProductHelperTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\Helper\ProductTestHelper;

/**
 * @group CommunityEdition
 */
class CreateInventoryLevelsTest extends ProductHelperTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testCreateInventoryLevels()
    {
        $this->createProduct();
        $this->assertInventoryLevelsCreated();
    }

    /**
     * @depends testCreateInventoryLevels
     */
    public function testAddAdditionalUnit()
    {
        $product = $this->getProductDataBySku(ProductTestHelper::TEST_SKU);
        $id = $product->getId();

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $this->assertEquals(
            1,
            $crawler->filterXPath("//nav/a[contains(text(),'" . ProductTestHelper::CATEGORY_MENU_NAME . "')]")->count()
        );
        $form = $crawler->selectButton('Save and Close')->form();

        $data = $form->getPhpValues()['oro_product'];
        $submittedData = $this->getSubmittedData($data, $product, $form);

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $this->assertInventoryLevelsCreated();
    }

    /**
     * check if inventory levels are created after updating/creating product
     */
    private function assertInventoryLevelsCreated()
    {
        $product = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->findOneBy(['sku' => ProductTestHelper::TEST_SKU]);
        $inventoryLevels = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(InventoryLevel::class)
            ->getRepository(InventoryLevel::class)
            ->findBy(['product' => $product]);
        $this->assertCount($product->getUnitPrecisions()->count(), $inventoryLevels);
    }

    /**
     * {@inheritDoc}
     */
    protected function getLocalization(): Localization
    {
        $localization = $this->getContainer()->get('doctrine')
            ->getRepository(Localization::class)
            ->findOneBy([]);

        if (!$localization) {
            throw new \LogicException('At least one localization must be defined');
        }

        return $localization;
    }

    /**
     * {@inheritDoc}
     */
    protected function getLocalizedName(Product $product, Localization $localization): AbstractLocalizedFallbackValue
    {
        $localizedName = null;
        foreach ($product->getNames() as $name) {
            $nameLocalization = $name->getLocalization();
            if ($nameLocalization && $nameLocalization->getId() === $localization->getId()) {
                $localizedName = $name;
                break;
            }
        }

        if (!$localizedName) {
            throw new \LogicException('At least one localized name must be defined');
        }

        return $localizedName;
    }
}
