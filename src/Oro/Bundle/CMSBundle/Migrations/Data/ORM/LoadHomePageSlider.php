<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadHomePageSlider extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAdminUserData::class
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);

        $slider = new ContentBlock();
        $slider->setOrganization($user->getOrganization());
        $slider->setOwner($user->getOwner());
        $slider->setAlias('home-page-slider');

        $title = new LocalizedFallbackValue();
        $title->setString('Home Page Slider');
        $slider->addTitle($title);

        $html = file_get_contents(__DIR__.'/data/frontpage_slider.html');

        $variant = new TextContentVariant();
        $variant->setDefault(true);
        $variant->setContent($html);
        $slider->addContentVariant($variant);

        $manager->persist($slider);
        $manager->flush($slider);
    }
}
