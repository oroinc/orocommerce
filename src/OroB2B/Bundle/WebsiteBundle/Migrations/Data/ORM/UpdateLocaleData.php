<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\Intl\Intl;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class UpdateLocaleData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $locales = $manager->getRepository('OroB2BWebsiteBundle:Locale')->findAll();

        $localeNames = Intl::getLocaleBundle()->getLocaleNames(true);

        foreach ($locales as $locale) {
            $locale->setCode(array_search($locale->getTitle(), $localeNames, true));
        }

        $manager->flush();
    }
}
