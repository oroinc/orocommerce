<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadWebsiteDemoData extends AbstractFixture implements ContainerAwareInterface
{
    use UserUtilityTrait;

    const US = 'US';
    /**
     * @var array
     */
    protected $webSites = [
        [
            'name' => self::US,
            'url' => 'http://www.us.com',
            'localizations' => ['en_US', 'es_MX'],
            'sharing' => ['Mexico', 'Canada'],
        ],
        [
            'name' => 'Australia',
            'url' => 'http://www.australia.com',
            'localizations' => ['en_AU'],
            'sharing' => null,
        ],
        [
            'name' => 'Mexico',
            'url' => 'http://www.mexico.com',
            'localizations' => ['es_MX'],
            'sharing' => [self::US, 'Canada'],
        ],
        [
            'name' => 'Canada',
            'url' => 'http://www.canada.com',
            'localizations' => ['fr_CA', 'en_CA'],
            'sharing' => [self::US, 'Mexico'],
        ],
        [
            'name' => 'Europe',
            'url' => 'http://www.europe.com',
            'localizations' => ['en_GB', 'fr', 'de'],
            'sharing' => null,
        ],
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $manager->flush();

        // Create websites
        foreach ($this->webSites as $webSite) {
            $site = new Website();

            $localizations = [];
            foreach ($webSite['localizations'] as $code) {
                $localizations[] = $this->getLocalization($code);
            }

            $site
                ->setName($webSite['name'])
                ->setUrl($webSite['url'])
                ->resetLocalizations($localizations)
                ->setOwner($businessUnit)
                ->setOrganization($organization);

            $manager->persist($site);
        }

        $manager->flush();

        // Create website sharing relationship
        foreach ($this->webSites as $webSite) {
            $site = $this->getWebsiteByName($manager, $webSite['name']);
            if ($webSite['sharing']) {
                foreach ($webSite['sharing'] as $siteName) {
                    $relatedWebsite = $this->getWebsiteByName($manager, $siteName);
                    $site->addRelatedWebsite($relatedWebsite);
                }
            }
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

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return Website
     */
    protected function getWebsiteByName(EntityManager $manager, $name)
    {
        $website = $manager->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => $name]);

        if (!$website) {
            throw new \LogicException(sprintf('There is no website with name "%s" .', $name));
        }

        return $website;
    }
}
