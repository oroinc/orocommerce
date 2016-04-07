<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

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
        ['name' => self::WEBSITE1, 'url' => 'http://www.us.com', 'locales' => ['en_US']],
        ['name' => self::WEBSITE2, 'url' => 'http://www.canada.com', 'locales' => ['en_CA']],
        ['name' => self::WEBSITE3, 'url' => 'http://www.canada-new.com', 'locales' => ['en_CA']],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [__NAMESPACE__ . '\LoadLocaleData'];
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

            $siteLocales = [];
            foreach ($webSite['locales'] as $localeCode) {
                $siteLocales[] = $this->getLocaleByCode($manager, $localeCode);
            }

            $site->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setName($webSite['name'])
                ->setUrl($webSite['url'])
                ->resetLocales($siteLocales);

            $this->setReference($site->getName(), $site);

            $manager->persist($site);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return Locale
     */
    protected function getLocaleByCode(EntityManager $manager, $code)
    {
        $locale = $manager->getRepository('OroB2BWebsiteBundle:Locale')->findOneBy(['code' => $code]);

        if (!$locale) {
            throw new \LogicException(sprintf('There is no locale with code "%s" .', $code));
        }

        return $locale;
    }
}
