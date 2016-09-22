<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Controller;

use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

/**
 * @dbIsolation
 */
class AuditControllerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * @var array
     */
    protected $userData = [
        'enabled'   => 1,
        'password'  => 'password',
        'firstName' => 'first name',
        'lastName'  => 'last name',
        'email'     => 'test@example.com',
        'account'   => 'AccountUser AccountUser',
    ];

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testAuditHistory()
    {
        if (!$this->client->getContainer()->hasParameter('oro_account.entity.account_user.class')) {
            $this->markTestSkipped('OroAccountBundle is not installed');
        }

        $accountUser = new AccountUser();
        $accountUser->setEmail($this->userData['email']);
        $accountUser->setPassword($this->userData['password']);

        $em = $this->getDoctrine()->getManagerForClass(AccountUser::class);
        $em->persist($accountUser);
        $em->flush();

        $sentMessages = $this->getMessageCollector()->getSentMessages();
        $sentMessages = array_filter($sentMessages, function ($message) {
            return $message['topic'] == Topics::ENTITIES_CHANGED;
        });

        $this->assertCount(1, $sentMessages);
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private function getDoctrine()
    {
        return self::getContainer()->get('doctrine');
    }
}
