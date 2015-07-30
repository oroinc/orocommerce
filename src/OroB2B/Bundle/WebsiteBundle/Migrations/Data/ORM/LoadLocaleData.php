<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LoadLocaleData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $locale = new Locale();
        $locale->setCode(
            Intl::getLocaleBundle()->getLocaleName(
                $this->container->get('oro_locale.settings')->getLanguage()
            )
        );

        $manager->persist($locale);
        /** @var EntityManager $manager */
        $manager->flush($locale);

        $this->addReference('default_website_locale', $locale);
    }
}
