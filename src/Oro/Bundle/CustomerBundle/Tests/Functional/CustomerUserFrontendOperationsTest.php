<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CustomerUserFrontendOperationsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadCustomerUserACLData::class
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

        /** @var CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);

        $user->setConfirmed(false);
        $this->getObjectManager()->flush();

        $this->executeOperation($user, 'oro_customer_customeruser_sendconfirmation');

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, Response::HTTP_OK);

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

        /** @var CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(false);
        $this->getObjectManager()->flush();

        $this->client->getContainer()->get('doctrine')->getManager()->clear();

        $this->executeOperation($user, 'oro_customer_customeruser_sendconfirmation');
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

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(false);
        $this->getObjectManager()->flush();

        $this->executeOperation($user, 'oro_customer_customeruser_confirm');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

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
        $applicationUrl = $configManager->get('oro_ui.application_url');
        $loginUrl = $applicationUrl . $this->getUrl('oro_customer_customer_user_security_login');

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
        /** @var CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);

        $user->setConfirmed(false);
        $this->getObjectManager()->flush();

        $this->executeOperation($user, 'oro_customer_customeruser_confirm');
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

        /** @var CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $this->assertTrue($user->isEnabled());

        $this->executeOperation($user, 'oro_customer_frontend_customeruser_disable');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $this->assertFalse($user->isEnabled());

        $this->executeOperation($user, 'oro_customer_frontend_customeruser_enable');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

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

        /** @var CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(false);
        $this->getObjectManager()->flush();
        $this->executeOperation($user, 'oro_customer_frontend_customeruser_enable');
        $this->assertSame($this->client->getResponse()->getStatusCode(), $status);

        /** @var CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
        $user->setConfirmed(true);
        $this->getObjectManager()->flush();
        $this->executeOperation($user, 'oro_customer_frontend_customeruser_disable');
        $this->assertSame($this->client->getResponse()->getStatusCode(), $status);
    }

    /**
     * @return array
     */
    public function accessGrantedDataProvider()
    {
        return [
            'parent customer: DEEP' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
            ],
            'same customer: LOCAL' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_DEEP,
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
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
            ],
            'same customer: LOCAL_VIEW_ONLY' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
            ],
            'parent customer: LOCAL' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
            ],
            'parent customer: DEEP_VIEW_ONLY' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
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
        return $this->getObjectManager()->getRepository('OroCustomerBundle:CustomerUser');
    }

    /**
     * {@inheritdoc}
     */
    protected function executeOperation(CustomerUser $customerUser, $operationName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_frontend_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_customer_frontend_customer_user_view',
                    'entityId' => $customerUser->getId(),
                    'entityClass' => 'Oro\Bundle\CustomerBundle\Entity\CustomerUser'
                ]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }
}
