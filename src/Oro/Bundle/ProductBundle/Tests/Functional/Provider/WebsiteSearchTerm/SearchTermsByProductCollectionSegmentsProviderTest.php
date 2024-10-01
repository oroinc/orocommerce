<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider\WebsiteSearchTerm;

use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermsByProductCollectionSegmentsProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadSearchTermData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SearchTermsByProductCollectionSegmentsProviderTest extends WebTestCase
{
    private SearchTermsByProductCollectionSegmentsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadSearchTermData::class,
        ]);

        $this->provider = self::getContainer()
            ->get('oro_product.provider.website_search_term.search_terms_by_product_collection_segments');
    }

    public function testGetRelatedSearchTermsWhenNoSegments(): void
    {
        self::assertSame([], iterator_to_array($this->provider->getRelatedSearchTerms([PHP_INT_MAX])));
    }

    public function testGetRelatedSearchTerms(): void
    {
        $segment = $this->getReference(LoadProductCollectionData::SEGMENT);
        $searchTerm = $this->getReference(LoadSearchTermData::SHOW_PRODUCT_COLLECTION);
        self::assertSame([$searchTerm], iterator_to_array($this->provider->getRelatedSearchTerms([$segment->getId()])));
    }
}
