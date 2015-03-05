<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LoadWebsiteData extends AbstractFixture
{
    protected $webSites = [
        ['name' => 'US', 'url' => 'www.us.com', 'locales' => ['en_US']],
        ['name' => 'Canada', 'url' => 'www.canada.com', 'locales' => ['en_CA']]
    ];

    /**
     * Load websites
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getUser($manager);
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

            $manager->persist($site);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param EntityManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(EntityManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
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
