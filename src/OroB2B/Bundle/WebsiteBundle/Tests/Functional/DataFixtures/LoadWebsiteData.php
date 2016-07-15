<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadWebsiteData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const WEBSITE1 = 'US';
    const WEBSITE2 = 'Canada';
    const WEBSITE3 = 'CA';

    /**
     * @var array
     */
    protected $webSites = [
        ['name' => self::WEBSITE1, 'url' => 'http://www.us.com', 'localizations' => ['en_US']],
        ['name' => self::WEBSITE2, 'url' => 'http://www.canada.com', 'localizations' => ['en_CA']],
        ['name' => self::WEBSITE3, 'url' => 'http://www.canada-new.com', 'localizations' => ['en_CA']],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData'];
    }

    /**
     * Load websites
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        // Create websites
        foreach ($this->webSites as $webSite) {
            $site = new Website();
            $site->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setName($webSite['name'])
                ->setUrl($webSite['url']);

            $this->setReference($site->getName(), $site);

            $manager->persist($site);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param string $code
     * @return Localization
     */
    protected function getLocalization($code)
    {
        return $this->getReference($code);
    }
}
