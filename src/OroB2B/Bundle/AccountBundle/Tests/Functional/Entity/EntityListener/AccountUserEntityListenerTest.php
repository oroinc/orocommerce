<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountUserEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadAccountUserData::class,
            LoadWebsiteData::class
        ]);
    }

    public function testPrePersist()
    {
        $accountUser = new AccountUser();

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(AccountUser::class);
        $em->persist($accountUser);

        $website = $accountUser->getWebsite();
        $this->assertInstanceOf(Website::class, $website);
    }
}
