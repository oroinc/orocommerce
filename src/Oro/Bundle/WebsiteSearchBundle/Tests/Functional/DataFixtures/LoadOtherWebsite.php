<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;
use Oro\Bundle\WebsiteBundle\Provider\CacheableWebsiteProvider;
use Oro\Component\Testing\Doctrine\Events;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadOtherWebsite extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const REFERENCE_OTHER_WEBSITE = 'other_website';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mainWebsite = $manager->getRepository(Website::class)
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

        $manager->getConnection()
            ->getEventManager()
            ->addEventListener(Events::ON_AFTER_TEST_TRANSACTION_ROLLBACK, $this);
    }

    /**
     * Will be executed when (if) this fixture will be rolled back
     */
    public function onAfterTestTransactionRollback(ConnectionEventArgs $args)
    {
        /** @var CacheableWebsiteProvider $provider */
        $provider = $this->container->get('oro_website.cacheable_website_provider');
        $provider->clearCache();
    }
}
