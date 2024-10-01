<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider\WebsiteSearchTerm;

use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermProductCollectionSegmentsProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadSearchTermData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SearchTermProductCollectionSegmentsProviderTest extends WebTestCase
{
    private SearchTermProductCollectionSegmentsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadSearchTermData::class,
        ]);

        $this->provider = self::getContainer()
            ->get('oro_product.tests.provider.website_search_term.search_term_product_collection_segments');
    }

    public function testGetSearchTermProductCollectionSegments(): void
    {
        $searchTerm = $this->getReference(LoadProductCollectionData::SEGMENT);

        self::assertSame([$searchTerm], $this->provider->getSearchTermProductCollectionSegments());
    }
}
