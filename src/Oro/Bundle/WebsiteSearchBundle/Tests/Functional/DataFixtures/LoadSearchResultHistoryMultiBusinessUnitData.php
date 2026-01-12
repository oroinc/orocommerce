<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;

/**
 * Loads search result history records with different business units but same organization.
 */
class LoadSearchResultHistoryMultiBusinessUnitData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
            LoadBusinessUnitData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $website = $manager->getRepository(Website::class)->findOneBy(['default' => true]);

        $searchTerm = 'multi_bu_test_term';
        $hash = md5(mb_strtolower($searchTerm));
        $createdAt = new \DateTime('today 10:00', new \DateTimeZone('UTC'));

        $this->createHistoryRecord(
            $manager,
            $website,
            $organization,
            $this->getReference(LoadBusinessUnitData::BUSINESS_UNIT_1),
            $searchTerm,
            $hash,
            $createdAt,
            5
        );

        $this->createHistoryRecord(
            $manager,
            $website,
            $organization,
            $this->getReference(LoadBusinessUnitData::BUSINESS_UNIT_2),
            $searchTerm,
            $hash,
            $createdAt,
            3
        );

        $manager->flush();
    }

    private function createHistoryRecord(
        ObjectManager $manager,
        Website $website,
        Organization $organization,
        BusinessUnit $businessUnit,
        string $searchTerm,
        string $hash,
        \DateTime $createdAt,
        int $resultsCount
    ): void {
        $history = new SearchResultHistory();
        $history
            ->setWebsite($website)
            ->setSearchTerm($searchTerm)
            ->setNormalizedSearchTermHash($hash)
            ->setResultType('product_search')
            ->setResultsCount($resultsCount)
            ->setOwner($businessUnit)
            ->setOrganization($organization)
            ->setCreatedAt($createdAt);

        $manager->persist($history);
    }
}
