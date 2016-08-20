<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadProductDemoMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use LoadDemoMetaDataTrait;

    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroProductBundle:Product');

        $this->addMetaFieldsData($manager, $repository->findAll());

        $manager->flush();
    }

    /**
     * @inheritdoc
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
        ];
    }
}
