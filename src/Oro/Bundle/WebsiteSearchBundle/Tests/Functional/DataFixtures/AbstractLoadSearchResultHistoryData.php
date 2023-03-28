<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;

abstract class AbstractLoadSearchResultHistoryData extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies()
    {
        return [
            LoadCustomerUserData::class,
            LoadLocalizationData::class
        ];
    }

    abstract protected function getCsvPath(): string;

    public function load(ObjectManager $manager)
    {
        $organization = $this->getOrganization($manager);

        /** @var Website $website */
        $website = $this->getWebsite($manager);

        $handler = fopen($this->getCsvPath(), 'r');
        $headers = fgetcsv($handler, 1000);

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $customer = null;
            $customerUser = null;
            $localization = null;
            if ($row['customer']) {
                /** @var Customer $customer */
                $customer = $this->getReference($row['customer']);
            }
            if ($row['customer_user']) {
                /** @var CustomerUser $customerUser */
                $customerUser = $this->getReference($row['customer_user']);
            }
            if ($row['localization']) {
                /** @var Localization $localization */
                $localization = $this->getReference($row['localization']);
            }

            $historyRecord = new SearchResultHistory();
            $historyRecord
                ->setWebsite($website)
                ->setCustomer($customer)
                ->setCustomerUser($customerUser)
                ->setLocalization($localization)
                ->setResultType($row['type'])
                ->setResultsCount($row['type'] === 'empty'? 0 :(int)$row['count'])
                ->setSearchTerm($row['term'])
                ->setCustomerVisitorId($row['visitor_id'] ? (int)$row['visitor_id'] : null)
                ->setNormalizedSearchTermHash(md5(mb_strtolower($row['term'])))
                ->setOwner($organization->getBusinessUnits()->first())
                ->setOrganization($organization)
                ->setCreatedAt(new \DateTime($row['created_at'], new \DateTimeZone('UTC')));

            $manager->persist($historyRecord);
            $this->setReference($row['reference'], $historyRecord);
        }

        fclose($handler);
        $manager->flush();
    }

    protected function getOrganization(ObjectManager $manager)
    {
        //Can not use reference here because this fixture is used in tests
        return $manager->getRepository(Organization::class)->findOneBy([], ['id' => 'ASC']);
    }

    protected function getWebsite(ObjectManager $manager)
    {
        return $manager->getRepository(Website::class)->findOneBy(['default' => true]);
    }
}
