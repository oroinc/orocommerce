<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AccountUserFrontendActionsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadAccountUserACLData::class
            ]
        );
    }

    /**
     * @dataProvider accessGrantedDataProvider
     *
     * @param string $login
     * @param string $resource
     */
    public function testSendConfirmation($login, $resource)
    {
        $this->loginUser($login);

        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);

        $user->setConfirmed(false);
        $this->getObjectManager()->flush();

        $this->executeOperation($user, 'oro_account_accountuser_sendconfirmation');

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        /** @var \Swift_Plugins_MessageLogger $emailLogging */
        $emailLogger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');
        $emailMessages = $emailLogger->getMessages();

        /** @var \Swift_Message $message */
        $message = reset($emailMessages);

        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($resource, key($message->getTo()));
        $this->assertContains('Confirmation of account registration', $message->getSubject());
        $this->assertContains($resource, $message->getBody());

        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(true);
        $this->getObjectManager()->flush();
    }

    /**
     * @dataProvider accessDeniedDataProvider
     *
     * @param string $login
     * @param string $resource
     * @param int $status
     */
    public function testSendConfirmationAccessDenied($login, $resource, $status)
    {
        $this->loginUser($login);

        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(false);
        $this->getObjectManager()->flush();

        $this->client->getContainer()->get('doctrine')->getManager()->clear();

        $this->executeOperation($user, 'oro_account_accountuser_sendconfirmation');
        $this->assertSame($status, $this->client->getResponse()->getStatusCode());

        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(true);
        $this->getObjectManager()->flush();
    }

    /**
     * @dataProvider accessGrantedDataProvider
     *
     * @param string $login
     * @param string $resource
     */
    public function testConfirmAccessGranted($login, $resource)
    {
        $this->loginUser($login);

        /** @var \Oro\Bundle\CustomerBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(false);
        $this->getObjectManager()->flush();

        $this->executeOperation($user, 'oro_account_accountuser_confirm');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $this->assertTrue($user->isConfirmed());

        /** @var \Swift_Plugins_MessageLogger $emailLogging */
        $emailLogger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');
        $emailMessages = $emailLogger->getMessages();

        $this->assertCount(1, $emailMessages);

        $message = array_shift($emailMessages);

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
            . $this->getUrl('oro_customer_account_user_security_login');

        $this->assertContains($loginUrl, $message->getBody());

        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $this->assertTrue($user->isConfirmed());
    }

    /**
     * @dataProvider accessDeniedDataProvider
     *
     * @param string $login
     * @param string $resource
     * @param int $status
     */
    public function testConfirmAccessDenied($login, $resource, $status)
    {
        $this->loginUser($login);
        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);

        $user->setConfirmed(false);
        $this->getObjectManager()->flush();

        $this->executeOperation($user, 'oro_account_accountuser_confirm');
        $this->assertSame($status, $this->client->getResponse()->getStatusCode());

        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(true);
        $this->getObjectManager()->flush();
    }

    /**
     * @dataProvider accessGrantedDataProvider
     *
     * @param string $login
     * @param string $resource
     */
    public function testEnableAndDisable($login, $resource)
    {
        $this->loginUser($login);

        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $this->assertTrue($user->isEnabled());

        $this->executeOperation($user, 'oro_account_frontend_accountuser_disable');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $this->assertFalse($user->isEnabled());

        $this->executeOperation($user, 'oro_account_frontend_accountuser_enable');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $this->assertTrue($user->isEnabled());


        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(true);
        $this->getObjectManager()->flush();
    }

    /**
     * @dataProvider accessDeniedDataProvider
     *
     * @param string $login
     * @param string $resource
     * @param int $status
     */
    public function testEnableAndDisableAccessDenied($login, $resource, $status)
    {
        $this->loginUser($login);

        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(false);
        $this->getObjectManager()->flush();
        $this->executeOperation($user, 'oro_account_frontend_accountuser_enable');
        $this->assertSame($this->client->getResponse()->getStatusCode(), $status);

        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(true);
        $this->getObjectManager()->flush();
        $this->executeOperation($user, 'oro_account_frontend_accountuser_disable');
        $this->assertSame($this->client->getResponse()->getStatusCode(), $status);
    }

    /**
     * @return array
     */
    public function accessGrantedDataProvider()
    {
        return [
            'parent account: DEEP' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
            ],
            'same account: LOCAL' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_DEEP,
            ],
        ];
    }

    /**
     * @return array
     */
    public function accessDeniedDataProvider()
    {
        return [
            'anonymous user' => [
                'login' => '',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 404,
            ],
            'same account: LOCAL_VIEW_ONLY' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 404,
            ],
            'parent account: LOCAL' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 404,
            ],
            'parent account: DEEP_VIEW_ONLY' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 404,
            ],
        ];
    }

    /**
     * @return EntityManagerInterface
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
        return $this->getObjectManager()->getRepository('OroCustomerBundle:AccountUser');
    }

    /**
     * {@inheritdoc}
     */
    protected function executeOperation(AccountUser $accountUser, $operationName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_frontend_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_customer_frontend_account_user_view',
                    'entityId' => $accountUser->getId(),
                    'entityClass' => 'Oro\Bundle\CustomerBundle\Entity\AccountUser'
                ]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }
}
