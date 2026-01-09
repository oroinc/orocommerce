<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\LoginPage;

/**
 * Data fixture that loads the default login page entity.
 *
 * Creates and persists a default login page during the database initialization process,
 * ensuring that a login page entity exists for the storefront.
 */
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
