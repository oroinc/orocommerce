<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional;

use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

abstract class AbstractAccountUserActionsTestCase extends WebTestCase
{
    const EMAIL = '';

    public function testConfirm()
    {
        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getReference(static::EMAIL);
        $this->assertNotNull($user);

        $id = $user->getId();

        $user->setConfirmed(false);
        $this->getObjectManager()->flush();
        $this->getObjectManager()->clear();

        $this->executeOperation($user, 'orob2b_account_accountuser_confirm');

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
        $this->assertContains($user->getEmail(), $message->getSubject());
        $this->assertContains($user->getEmail(), $message->getBody());

        $configManager = $this->getContainer()->get('oro_config.manager');
        $loginUrl = trim($configManager->get('oro_ui.application_url'), '/')
            . $this->getUrl('orob2b_account_account_user_security_login');

        $this->assertContains($loginUrl, $message->getBody());

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $user = $this->getUserRepository()->find($id);

        $this->assertNotNull($user);
        $this->assertTrue($user->isConfirmed());
    }

    public function testSendConfirmation()
    {
        /** @var AccountUser $user */
        $email = static::EMAIL;

        $user = $this->getReference($email);
        $user->setConfirmed(false);
        $this->getObjectManager()->flush();
        $this->getObjectManager()->clear();

        $this->executeOperation($user, 'orob2b_account_accountuser_sendconfirmation');

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
    }

    public function testEnableAndDisable()
    {
        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => static::EMAIL]);
        $id = $user->getId();

        $this->assertNotNull($user);
        $this->assertTrue($user->isEnabled());

        $this->executeOperation($user, $this->getAccountUserDisableOperationName());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->getObjectManager()->clear();

        $user = $this->getUserRepository()->find($id);
        $this->assertFalse($user->isEnabled());

        $this->executeOperation($user, $this->getAccountUserEnableOperationName());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->getObjectManager()->clear();

        $user = $this->getUserRepository()->find($id);
        $this->assertTrue($user->isEnabled());
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

    /**
     * @param AccountUser $accountUser
     * @param string $operationName
     */
    abstract protected function executeOperation(AccountUser $accountUser, $operationName);

    /**
     * @return string
     */
    abstract protected function getAccountUserEnableOperationName();

    /**
     * @return string
     */
    abstract protected function getAccountUserDisableOperationName();
}
