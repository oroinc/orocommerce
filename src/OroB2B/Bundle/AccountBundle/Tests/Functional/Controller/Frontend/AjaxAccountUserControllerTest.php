<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData as LoadLoginAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AjaxAccountUserControllerTest extends WebTestCase
{
    const NAME_PREFIX = 'NamePrefix';
    const MIDDLE_NAME = 'MiddleName';
    const NAME_SUFFIX = 'NameSuffix';
    const EMAIL = 'first@example.com';
    const FIRST_NAME = 'John';
    const LAST_NAME = 'Doe';

    const UPDATED_NAME_PREFIX = 'UNamePrefix';
    const UPDATED_FIRST_NAME = 'UFirstName';
    const UPDATED_MIDDLE_NAME = 'UMiddleName';
    const UPDATED_LAST_NAME = 'UpdLastName';
    const UPDATED_NAME_SUFFIX = 'UNameSuffix';
    const UPDATED_EMAIL = 'updated@example.com';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadLoginAccountUserData::AUTH_USER, LoadLoginAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
        );
        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    public function testEnableAndDisable()
    {
        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => 'account.user2@test.com']);
        $id = $user->getId();

        $this->assertNotNull($user);
        $this->assertTrue($user->isEnabled());

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_disable', ['id' => $id])
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->getObjectManager()->clear();

        $user = $this->getUserRepository()->find($id);
        $this->assertFalse($user->isEnabled());

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_enable', ['id' => $id])
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->getObjectManager()->clear();

        $user = $this->getUserRepository()->find($id);
        $this->assertTrue($user->isEnabled());
    }

    public function testConfirm()
    {
        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => 'account.user2@test.com']);
        $this->assertNotNull($user);

        $id = $user->getId();

        $user->setConfirmed(false);
        $this->getObjectManager()->flush();
        $this->getObjectManager()->clear();
        $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_confirm', ['id' => $id]));

        /** @var MessageDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('swiftmailer');
        $collectedMessages = $collector->getMessages();

        $this->assertCount(1, $collectedMessages);

        $message = array_shift($collectedMessages);

        $this->assertInstanceOf('\Swift_Message', $message);
        $this->assertEquals($user->getEmail(), key($message->getTo()));
        $this->assertEquals(
            $this->getContainer()->get('oro_config.manager')->get('oro_notification.email_notification_sender_email'),
            key($message->getFrom())
        );
        $this->assertContains($user->getEmail(), $message->getBody());
        $this->assertContains($user->getEmail(), $message->getSubject());

        $configManager = $this->getContainer()->get('oro_config.manager');
        $loginUrl = trim($configManager->get('oro_ui.application_url'), '/')
            . $this->getUrl('orob2b_account_account_user_security_login');

        $this->assertContains($loginUrl, $message->getBody());

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('successful', $data);
        $this->assertTrue($data['successful']);

        $user = $this->getUserRepository()->find($id);

        $this->assertNotNull($user);
        $this->assertTrue($user->isConfirmed());
    }

    public function testSendConfirmation()
    {
        /** @var AccountUser $accountUser */
        $email = 'account.user2@test.com';
        $accountUser = $this->getReference($email);
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_send_confirmation', ['id' => $accountUser->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        /** @var MessageDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('swiftmailer');
        $messages = $collector->getMessages();

        /** @var \Swift_Message $message */
        $message = reset($messages);

        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($email, key($message->getTo()));
        $this->assertContains('Confirmation of account registration', $message->getSubject());
        $this->assertContains($email, $message->getBody());

        $this->assertJson(
            json_encode(['successful' => true, 'message' => 'Confirmation email has been sent']),
            $result->getContent()
        );
    }

    public function testGetAccountIdAction()
    {
        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => 'account.user2@test.com']);

        $this->assertNotNull($user);

        $id = $user->getId();

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_get_account', ['id' => $id])
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('accountId', $data);

        $accountId = $user->getAccount() ? $user->getAccount()->getId() : null;

        $this->assertEquals($data['accountId'], $accountId);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BAccountBundle:AccountUser');
    }
}
