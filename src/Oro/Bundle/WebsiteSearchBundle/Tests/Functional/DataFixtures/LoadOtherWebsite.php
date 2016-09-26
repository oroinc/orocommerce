<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

class LoadOtherWebsite extends AbstractFixture
{
    const REFERENCE_OTHER_WEBSITE = 'other_website';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mainWebsite = $manager
            ->getRepository('OroWebsiteBundle:Website')
            ->findOneBy(['name' => LoadWebsiteData::DEFAULT_WEBSITE_NAME]);

        $website = new Website();
        $website
            ->setName('Other website')
            ->setOrganization($mainWebsite->getOrganization())
            ->setOwner($mainWebsite->getOwner())
            ->setDefault(false);

        $this->addReference(self::REFERENCE_OTHER_WEBSITE, $website);

        $manager->persist($website);
        /** @var EntityManager $manager */
        $manager->flush($website);
    }
}
