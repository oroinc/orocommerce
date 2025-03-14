<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\LoginPage;

class LoadLoginPage extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $loginPage = new LoginPage();
        $manager->persist($loginPage);
        $manager->flush();
    }
}
