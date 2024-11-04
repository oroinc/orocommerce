<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider\WebsiteSearchTerm;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermsByProductCollectionSegmentsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchTermsByProductCollectionSegmentsProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;

    private SearchTermsByProductCollectionSegmentsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new SearchTermsByProductCollectionSegmentsProvider($this->doctrine);
    }

    public function testGetRelatedSearchTermsWhenNoSegmentIds(): void
    {
        $this->doctrine
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getRelatedSearchTerms([]));
    }
}
