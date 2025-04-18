<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider\WebsiteSearchTerm;

use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermsIndexDataProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadSearchTermData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SearchTermsIndexDataProviderTest extends WebTestCase
{
    private SearchTermsIndexDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadSearchTermData::class,
        ]);

        $this->provider = self::getContainer()
            ->get('oro_product.tests.provider.website_search_term.search_terms_index_data');
    }

    public function testGetSearchTermsDataForProductsWhenNoProducts(): void
    {
        self::assertSame([], $this->provider->getSearchTermsDataForProducts([]));
    }

    public function testGetSearchTermsDataForProductsWhenMissingProduct(): void
    {
        self::assertSame([], $this->provider->getSearchTermsDataForProducts([self::BIGINT]));
    }

    public function testGetSearchTermsDataForProductsWhenHasRelatedProduct(): void
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $segment = $this->getReference(LoadProductCollectionData::SEGMENT);
        $searchTerm = $this->getReference(LoadSearchTermData::SHOW_PRODUCT_COLLECTION);

        self::assertSame(
            [
                [
                    'searchTermId' => $searchTerm->getId(),
                    'productCollectionSegmentId' => $segment->getId(),
                    'productCollectionProductId' => $product1->getId(),
                ],
            ],
            $this->provider->getSearchTermsDataForProducts([$product1->getId()])
        );
    }
}
