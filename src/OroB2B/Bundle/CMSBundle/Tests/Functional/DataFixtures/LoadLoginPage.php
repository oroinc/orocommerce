<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;

class LoadLoginPage extends AbstractFixture
{
    const LOGIN_PAGE_UNIQUE_REFERENCE = 'login_page_unique_test';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $loginPage = new LoginPage();
        $manager->persist($loginPage);
        $this->addReference(static::LOGIN_PAGE_UNIQUE_REFERENCE, $loginPage);
        $manager->flush();
    }
}
