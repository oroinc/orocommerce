<?php

namespace OroB2B\Bundle\SEOBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPageDemoMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use LoadDemoMetaDataTrait;

    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroB2BCMSBundle:Page');

        $this->addMetaFieldsData($manager, $repository->findAll());

        $manager->flush();
    }

    /**
     * @inheritdoc
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CMSBundle\Migrations\Data\Demo\ORM\LoadPageDemoData',
        ];
    }
}
