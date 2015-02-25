<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\CategoryTitle;

class LoadMasterCatalogRoot extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $title = new CategoryTitle();
        $title->setValue('Master catalog');

        $category = new Category();
        $category->addTitle($title);

        $manager->persist($category);
        $manager->flush($category);
    }
}
