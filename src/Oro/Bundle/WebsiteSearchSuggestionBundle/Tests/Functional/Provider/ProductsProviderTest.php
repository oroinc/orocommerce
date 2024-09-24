<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\Provider;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Provider\ProductsProvider;

final class ProductsProviderTest extends WebTestCase
{
    private ProductsProvider $productsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductData::class
        ]);

        $this->productsProvider = self::getContainer()->get('oro_website_search_suggestion.products_provider');
    }

    public function testGetProductsGroupedByOrganization(): void
    {
        $result = $this->productsProvider->getListOfProductIdAndOrganizationId();

        $productsGroupedByOrganization = [];

        foreach ($result as $item) {
            $productsGroupedByOrganization[$item['organizationId']][] = $item['id'];
        }

        self::assertCount(
            7,
            $productsGroupedByOrganization[$this->getReference(LoadOrganization::ORGANIZATION)->getId()]
        );
    }

    public function testGetProductSkuAndNames(): void
    {
        $result = $this->productsProvider->getProductsSkuAndNames([]);

        self::assertCount(7, $result);
    }
}
