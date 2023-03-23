<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchTermReport;

/**
 * Loads search term report records.
 */
class LoadSearchTermReportData extends AbstractFixture
{
    protected function getOrganization(ObjectManager $manager)
    {
        return $manager->getRepository(Organization::class)->findOneBy([], ['id' => 'ASC']);
    }

    protected function getCsvPath(): string
    {
        return __DIR__.'/data/search_term_report.csv';
    }

    public function load(ObjectManager $manager)
    {
        $organization = $this->getOrganization($manager);

        $handler = fopen($this->getCsvPath(), 'r');
        $headers = fgetcsv($handler, 1000);

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $reportRecord = new SearchTermReport();
            $reportRecord
                ->setSearchTerm($row['term'])
                ->setNormalizedSearchTermHash(md5(mb_strtolower($row['term'])))
                ->setSearchDate(new \DateTime($row['search_date'], new \DateTimeZone('UTC')))
                ->setTimesSearched($row['times_searched'])
                ->setTimesReturnedResults($row['times_not_empty'])
                ->setTimesEmpty($row['times_empty'])
                ->setOwner($organization->getBusinessUnits()->first())
                ->setOrganization($organization);

            $this->setReference($row['reference'], $reportRecord);
            $manager->persist($reportRecord);
        }

        fclose($handler);
        $manager->flush();
    }
}
