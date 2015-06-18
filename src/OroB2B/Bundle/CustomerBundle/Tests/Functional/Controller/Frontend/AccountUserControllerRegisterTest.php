<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

/**
 * @dbIsolation
 */
class AccountUserControllerRegisterTest extends WebTestCase
{
    const EMAIL = 'create.user@example.com';

    protected function setUp()
    {
        $this->initClient();
    }

    public function testRegisterPasswordMismatch()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_register'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Register')->form();

        $submittedData = [
            'orob2b_customer_frontend_account_user_register' => [
                '_token' => $form->get('orob2b_customer_frontend_account_user_register[_token]')->getValue(),
                'firstName' => 'Created',
                'lastName' => 'User',
                'email' => self::EMAIL,
                'plainPassword' => [
                    'first' => 'plainPassword',
                    'second' => 'plainPassword2'
                ]
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form, $submittedData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEmpty($this->getAccountUsers(['email' => self::EMAIL, 'enabled' => true]));

        $this->assertContains('The password fields must match.', $crawler->filter('.validation-failed')->html());
    }

    public function testRegister()
    {
        $configManager = $this->getContainer()->get('oro_config.manager');
        $isConfirmationRequired = $configManager->get('oro_b2b_customer.confirmation_required');
        $configManager->set('oro_b2b_customer.confirmation_required', false);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_register'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Register')->form();
        $submittedData = [
            'orob2b_customer_frontend_account_user_register' => [
                '_token' => $form->get('orob2b_customer_frontend_account_user_register[_token]')->getValue(),
                'firstName' => 'Created',
                'lastName' => 'User',
                'email' => self::EMAIL,
                'plainPassword' => [
                    'first' => 'plainPassword',
                    'second' => 'plainPassword'
                ]
            ]
        ];

        $this->client->followRedirects(false);

        $this->client->submit($form, $submittedData);

        /** @var MessageDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('swiftmailer');
        $messages = $collector->getMessages();

        /** @var \Swift_Message $message */
        $message = reset($messages);

        $this->assertInstanceOf('Swift_Message', $message);

        $this->assertEquals(self::EMAIL, key($message->getTo()));

        $this->assertContains(self::EMAIL, $message->getSubject());
        $this->assertContains(self::EMAIL, $message->getBody());
        $this->assertContains('plainPassword', $message->getBody());

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertNotEmpty($this->getAccountUsers(['email' => self::EMAIL]));
        $this->assertContains('Registration successful', $crawler->filter('.alert-success')->html());

        $configManager->set('oro_b2b_customer.confirmation_required', $isConfirmationRequired);
        $configManager->flush();
    }

    /**
     * @depends testRegister
     */
    public function testRegisterExistingEmail()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_register'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Register')->form();
        $submittedData = [
            'orob2b_customer_frontend_account_user_register' => [
                '_token' => $form->get('orob2b_customer_frontend_account_user_register[_token]')->getValue(),
                'firstName' => 'Created',
                'lastName' => 'User',
                'email' => self::EMAIL,
                'plainPassword' => [
                    'first' => 'plainPassword',
                    'second' => 'plainPassword'
                ]
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form, $submittedData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('This value is already used.', $crawler->filter('.validation-failed')->html());
    }

    /**
     * @param array $criteria
     * @return AccountUser[]
     */
    protected function getAccountUsers(array $criteria)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BCustomerBundle:AccountUser')
            ->getRepository('OroB2BCustomerBundle:AccountUser')
            ->findOneBy($criteria);
    }
}
