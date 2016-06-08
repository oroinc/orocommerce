<?php

namespace OroB2B\Bundle\SEOBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPageDemoMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use LoadDemoMetaDataTrait;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroB2BCMSBundle:Page');

        $this->addMetaFieldsData($manager, $repository->findAll());

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CMSBundle\Migrations\Data\Demo\ORM\LoadPageDemoData',
        ];
    }
}
