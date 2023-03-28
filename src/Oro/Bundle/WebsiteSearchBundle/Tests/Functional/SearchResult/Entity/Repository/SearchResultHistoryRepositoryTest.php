<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchResult\Entity\Repository;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsite;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchResultHistoryRepository;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryOldData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryPart1Data;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryPart2Data;

/**
 * @dbIsolationPerTest
 */
class SearchResultHistoryRepositoryTest extends WebTestCase
{
    private SearchResultHistoryRepository $repo;

    protected function setUp(): void
    {
        $this->initClient();
        $this->repo = self::getContainer()->get('doctrine')->getRepository(SearchResultHistory::class);
    }

    public function testGetOrganizationsByHistory()
    {
        $this->loadFixtures([
            LoadSearchResultHistoryOldData::class,
            LoadSearchResultHistoryPart1Data::class,
            LoadSearchResultHistoryPart2Data::class,
        ]);
        $organizationsIds = [];
        foreach ($this->repo->getOrganizationsByHistory() as $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $organizationsIds[] = $org->getId();
        }

        $orgId = $this->getReference('search_result_scrubs')->getOrganization()->getId();
        $this->assertEquals([$orgId], $organizationsIds);
    }

    public function testRemoveOldRecords()
    {
        $this->loadFixtures([
            LoadSearchResultHistoryOldData::class,
            LoadSearchResultHistoryPart1Data::class,
            LoadSearchResultHistoryPart2Data::class,
        ]);

        $oldEntityId = $this->getReference('search_result_scrubs')->getId();
        $freshEntityId = $this->getReference('search_result_led_light_2')->getId();

        $this->assertEquals(9, $this->repo->count([]));
        $this->repo->removeOldRecords(30);
        $this->assertEquals(5, $this->repo->count([]));

        $this->assertNull($this->repo->findOneBy(['id' => $oldEntityId]));
        $this->assertNotNull($this->repo->findOneBy(['id' => $freshEntityId]));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpsertSearchHistoryRecord()
    {
        $this->loadFixtures([
            LoadCustomerUserData::class,
            LoadLocalizationData::class,
            LoadWebsite::class,
        ]);

        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $localization = $this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE);
        $website = $this->getReference(LoadWebsite::WEBSITE);
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $businessUnit = $organization->getBusinessUnits()->first();
        $sessionId = 'abc';

        $searchTerm = 'search term';
        $resultType = 'product_search';
        $resultsCount = 10;
        $searchTermHash = md5($searchTerm);

        # Insert new record
        $this->repo->upsertSearchHistoryRecord(
            $searchTerm,
            $resultType,
            $resultsCount,
            $searchTermHash,
            $businessUnit->getId(),
            $website->getId(),
            $sessionId,
            $localization->getId(),
            $customerUser->getCustomer()->getId(),
            $customerUser->getId(),
            null,
            $organization->getId()
        );

        $record = $this->repo->findOneBy(['searchTerm' => $searchTerm]);
        $this->assertNotNull($record);

        $this->assertEquals($resultType, $record->getResultType());
        $this->assertEquals($resultsCount, $record->getResultsCount());
        $this->assertEquals($searchTermHash, $record->getNormalizedSearchTermHash());
        $this->assertEquals($businessUnit->getId(), $record->getOwner()->getId());
        $this->assertEquals($sessionId, $record->getSearchSession());
        $this->assertEquals($localization->getId(), $record->getLocalization()->getId());
        $this->assertEquals($website->getId(), $record->getWebsite()->getId());
        $this->assertEquals($customerUser->getCustomer()->getId(), $record->getCustomer()->getId());
        $this->assertEquals($customerUser->getId(), $record->getCustomerUser()->getId());
        $this->assertNull($record->getCustomerVisitorId());
        $this->assertEquals($organization->getId(), $record->getOrganization()->getId());

        # Update existing record on conflict on session_id
        $this->repo->upsertSearchHistoryRecord(
            $searchTerm.' updated',
            'empty',
            0,
            md5($searchTerm.' updated'),
            $businessUnit->getId(),
            $website->getId(),
            $sessionId,
            $localization->getId(),
            $customerUser->getCustomer()->getId(),
            $customerUser->getId(),
            null,
            $organization->getId()
        );

        $this->assertEquals(1, $this->repo->count([]));
        self::getContainer()->get('doctrine')
            ->getManagerForClass(SearchResultHistory::class)
            ->refresh($record);
        $this->assertNotNull($record);

        $this->assertEquals($searchTerm.' updated', $record->getSearchTerm());
        $this->assertEquals('empty', $record->getResultType());
        $this->assertEquals(0, $record->getResultsCount());
        $this->assertEquals(md5($searchTerm.' updated'), $record->getNormalizedSearchTermHash());
        $this->assertEquals($businessUnit->getId(), $record->getOwner()->getId());
        $this->assertEquals($sessionId, $record->getSearchSession());
        $this->assertEquals($localization->getId(), $record->getLocalization()->getId());
        $this->assertEquals($website->getId(), $record->getWebsite()->getId());
        $this->assertEquals($customerUser->getCustomer()->getId(), $record->getCustomer()->getId());
        $this->assertEquals($customerUser->getId(), $record->getCustomerUser()->getId());
        $this->assertNull($record->getCustomerVisitorId());
        $this->assertEquals($organization->getId(), $record->getOrganization()->getId());

        # Insert new record with new session_id
        $this->repo->upsertSearchHistoryRecord(
            $searchTerm.' updated',
            'empty',
            0,
            md5($searchTerm.' updated'),
            $businessUnit->getId(),
            $website->getId(),
            'new_session',
            null,
            null,
            null,
            42,
            $organization->getId()
        );
        $this->assertEquals(2, $this->repo->count([]));
    }
}
