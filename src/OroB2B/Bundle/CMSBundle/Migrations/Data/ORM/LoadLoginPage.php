<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;

class LoadLoginPage extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $loginPage = new LoginPage();
        $manager->persist($loginPage);
        $manager->flush();
    }
}
