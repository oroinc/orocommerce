<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface as DatagridManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;

class EnsureProductEntityProxiesNotInitializedOnPlpTest extends FrontendWebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader(
            LoadCustomerUserData::AUTH_USER,
            LoadCustomerUserData::AUTH_PW
        ));

        $this->loadFixtures([
            LoadFrontendProductData::class
        ]);
        $this->reindexProductWebsiteSearch();
        $this->getEntityManager()->clear();

        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);
        // A request needed for emulation a storefront request
        $this->emulateRequest();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
    }

    private function getDatagridManager(): DatagridManager
    {
        return self::getContainer()->get('oro_datagrid.datagrid.manager');
    }

    private function reindexProductWebsiteSearch(): void
    {
        $indexer = self::getContainer()->get('oro_website_search.indexer');
        $indexer->resetIndex(Product::class);
        $indexer->reindex(Product::class);
    }

    public function testProductEntityProxiesNotInitializedOnFrontendProductSearchGrid(): void
    {
        $this->client->requestFrontendGrid('frontend-product-search-grid');

        $identityMap = $this->getEntityManager()->getUnitOfWork()->getIdentityMap();
        self::assertArrayHasKey(Product::class, $identityMap);
        $products = $identityMap[Product::class] ?? [];
        self::assertNotEmpty($products);

        foreach ($products as $product) {
            self::assertInstanceOf('Proxies\__CG__\Oro\Bundle\ProductBundle\Entity\Product', $product);
            if ($product->__isInitialized__) {
                self::fail(sprintf(
                    'Failed asserting that all product entity proxies are not initialized.'
                    . ' The proxy for the product "%s" was initialized.',
                    $product->getSku()
                ));
            }
        }
    }
}
