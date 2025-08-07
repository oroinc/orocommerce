<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchTermReport;

/**
 * Loads search term report records.
 */
class LoadSearchTermReportData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadBusinessUnit::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        /** @var BusinessUnit $owner */
        $owner = $this->getReference(LoadBusinessUnit::BUSINESS_UNIT);

        $handler = fopen(__DIR__ . '/data/search_term_report.csv', 'r');
        $headers = fgetcsv($handler, 1000);
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $reportRecord = new SearchTermReport();
            $reportRecord->setSearchTerm($row['term']);
            $reportRecord->setNormalizedSearchTermHash(md5(mb_strtolower($row['term'])));
            $reportRecord->setSearchDate(new \DateTime($row['search_date'], new \DateTimeZone('UTC')));
            $reportRecord->setTimesSearched($row['times_searched']);
            $reportRecord->setTimesReturnedResults($row['times_not_empty']);
            $reportRecord->setTimesEmpty($row['times_empty']);
            $reportRecord->setOrganization($organization);
            $reportRecord->setOwner($owner);

            $this->setReference($row['reference'], $reportRecord);
            $manager->persist($reportRecord);
        }
        fclose($handler);
        $manager->flush();
    }
}
