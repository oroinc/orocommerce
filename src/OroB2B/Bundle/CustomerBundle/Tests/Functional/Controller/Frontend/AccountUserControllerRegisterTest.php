<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

/**
 * @dbIsolation
 */
class AccountUserControllerRegisterTest extends WebTestCase
{

    const EMAIL = 'john.doe@example.com';
    const PASSWORD = '123456';

    /** @var  bool */
    protected $isConfirmationRequired;

    /** @var  bool */
    protected $sendPassword;

    protected function setUp()
    {
        $this->initClient();
        $configManager = $this->getContainer()->get('oro_config.manager');
        $this->isConfirmationRequired = $configManager->get('oro_b2b_customer.confirmation_required');
        $this->sendPassword = $configManager->get('oro_b2b_customer.send_password_in_welcome_email');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set('oro_b2b_customer.confirmation_required', $this->isConfirmationRequired);
        $configManager->set('oro_b2b_customer.send_password_in_welcome_email', $this->sendPassword);
        $configManager->flush();
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
                'firstName' => 'Jim',
                'lastName' => 'Brown',
                'email' => 'jim.brown@example.com',
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

        $this->assertEmpty($this->getAccountUser(['email' => 'jim.brown@example.com', 'enabled' => true]));

        $this->assertContains('The password fields must match.', $crawler->filter('.validation-failed')->html());
    }

    /**
     * @dataProvider registerWithoutConfirmationDataProvider
     * @param string $email
     * @param bool $withPassword
     */
    public function testRegisterWithoutConfirmation($email, $withPassword)
    {
        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set('oro_b2b_customer.confirmation_required', false);

        $configManager->set('oro_b2b_customer.send_password_in_welcome_email', $withPassword);

        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_register'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->submitRegisterForm($crawler, $email);

        /** @var MessageDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('swiftmailer');
        $messages = $collector->getMessages();

        /** @var \Swift_Message $message */
        $message = reset($messages);

        $this->assertInstanceOf('Swift_Message', $message);

        $this->assertEquals($email, key($message->getTo()));

        $this->assertContains($email, $message->getSubject());
        $this->assertContains($email, $message->getBody());

        $this->assertContains(
            trim($configManager->get('oro_ui.application_url'), '/')
            . $this->getUrl('orob2b_customer_account_user_security_login'),
            $message->getBody()
        );

        if ($withPassword) {
            $this->assertContains(self::PASSWORD, $message->getBody());
        } else {
            $this->assertNotContains(self::PASSWORD, $message->getBody());
        }

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $user = $this->getAccountUser(['email' => $email]);
        $this->assertNotEmpty($user);
        $this->assertTrue($user->isEnabled());
        $this->assertTrue($user->isConfirmed());

        $this->assertContains('Registration successful', $crawler->filter('.alert-success')->html());
    }

    /**
     * @return array
     */
    public function registerWithoutConfirmationDataProvider()
    {
        return [
            'with password' => [
                'email' => 'adam.smith@example.com',
                'withPassword' => true
            ],
            'without password' => [
                'email' => 'sam.black@example.com',
                'withPassword' => false
            ]
        ];
    }

    public function testRegisterWithConfirmation()
    {
        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set('oro_b2b_customer.confirmation_required', true);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_register'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->submitRegisterForm($crawler, self::EMAIL);

        /** @var MessageDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('swiftmailer');
        $messages = $collector->getMessages();

        /** @var \Swift_Message $message */
        $message = reset($messages);

        $this->assertInstanceOf('Swift_Message', $message);

        $this->assertEquals(self::EMAIL, key($message->getTo()));

        $this->assertContains('Confirmation of account registration', $message->getSubject());

        $user = $this->getAccountUser(['email' => self::EMAIL]);

        $confirmMessage = 'Please follow this link to confirm your email address: <a href="'
            . trim($configManager->get('oro_ui.application_url'), '/')
            . htmlspecialchars($this->getUrl(
                'orob2b_customer_frontend_account_user_confirmation',
                [
                    'username' => $user->getUsername(),
                    'token' => $user->getConfirmationToken()
                ]
            ))
            . '">Confirm</a>';
        $this->assertContains($confirmMessage, $message->getBody());

        $user = $this->getAccountUser(['email' => self::EMAIL]);
        $this->assertNotEmpty($user);
        $this->assertTrue($user->isEnabled());
        $this->assertFalse($user->isConfirmed());

        $crawler = $this->client->followRedirect();
        $this->assertEquals('Login', $crawler->filter('h2.title')->html());
        $this->assertContains(
            'Please check your email to complete registration',
            $crawler->filter('.alert-success')->html()
        );

        $this->client->followRedirects(true);

        // Follow confirmation link
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_customer_frontend_account_user_confirmation',
                [
                    'username' => $user->getUsername(),
                    'token' => $user->getConfirmationToken()
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Login', $crawler->html());

        $user = $this->getAccountUser(['email' => self::EMAIL]);
        $this->assertNotEmpty($user);
        $this->assertTrue($user->isEnabled());
        $this->assertTrue($user->isConfirmed());
    }

    /**
     * @depends testRegisterWithConfirmation
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
                    'first' => self::PASSWORD,
                    'second' => self::PASSWORD
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
     * @depends testRegisterWithConfirmation
     */
    public function testResetPassword()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_account_user_security_login'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Login', $crawler->filter('h2.title')->html());

        $forgotPasswordLink = $crawler->filter('a:contains("Forgot Your Password?")')->link();
        $crawler = $this->client->click($forgotPasswordLink);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Forgot Your Password', $crawler->filter('h2.title')->html());

        $this->assertUnknownEmail($crawler);
        $this->assertKnownEmail($crawler);

        // Follow reset password link
        $user = $this->getAccountUser(['email' => self::EMAIL]);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_customer_frontend_account_user_password_reset',
                [
                    'token' => $user->getConfirmationToken(),
                    'username' => $user->getUsername()
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Create New Password', $crawler->filter('h2.title')->html());

        $form = $crawler->selectButton('Create')->form();

        $submittedData = [
            'orob2b_customer_account_user_password_reset' => [
                '_token' => $form->get('orob2b_customer_account_user_password_reset[_token]')->getValue(),
                'plainPassword' => [
                    'first' => '654321',
                    'second' => '654321'
                ]
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form, $submittedData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Login', $crawler->filter('h2.title')->html());
        $this->assertContains('Password was created successfully.', $crawler->filter('.alert-success')->html());
    }

    /**
     * @param array $criteria
     * @return AccountUser
     */
    protected function getAccountUser(array $criteria)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BCustomerBundle:AccountUser')
            ->getRepository('OroB2BCustomerBundle:AccountUser')
            ->findOneBy($criteria);
    }

    /**
     * @param Crawler $crawler
     * @param string $email
     * @return Crawler
     */
    protected function submitRegisterForm(Crawler $crawler, $email)
    {
        $form = $crawler->selectButton('Register')->form();
        $submittedData = [
            'orob2b_customer_frontend_account_user_register' => [
                '_token' => $form->get('orob2b_customer_frontend_account_user_register[_token]')->getValue(),
                'firstName' => 'First Name',
                'lastName' => 'Last Name',
                'email' => $email,
                'plainPassword' => [
                    'first' => self::PASSWORD,
                    'second' => self::PASSWORD
                ]
            ]
        ];

        $this->client->followRedirects(false);

        return $this->client->submit($form, $submittedData);
    }

    /**
     * @param Crawler $crawler
     */
    protected function assertUnknownEmail(Crawler $crawler)
    {
        $unknownEmail = 'unknown@example.com';
        $form = $crawler->selectButton('Request')->form();
        $submittedData = [
            'orob2b_customer_account_user_password_request' => [
                '_token' => $form->get('orob2b_customer_account_user_password_request[_token]')->getValue(),
                'email' => $unknownEmail
            ]
        ];

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form, $submittedData);
        $this->assertEquals('Forgot Your Password', $crawler->filter('h2.title')->html());
        $this->assertContains(
            'Email address "'. $unknownEmail .'" is not known',
            $crawler->filter('.alert-error')->html()
        );
    }

    /**
     * @param Crawler $crawler
     */
    protected function assertKnownEmail(Crawler $crawler)
    {
        $form = $crawler->selectButton('Request')->form();
        $submittedData = [
            'orob2b_customer_account_user_password_request' => [
                '_token' => $form->get('orob2b_customer_account_user_password_request[_token]')->getValue(),
                'email' => self::EMAIL
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

        $this->assertContains('Reset Account User Password', $message->getSubject());
        $this->assertContains('To reset your password - please visit', $message->getBody());
        $this->assertContains(self::EMAIL, $message->getBody());

        $configManager = $this->getContainer()->get('oro_config.manager');
        $user = $this->getAccountUser(['email' => self::EMAIL]);
        $resetUrl = trim($configManager->get('oro_ui.application_url'), '/')
            . htmlspecialchars($this->getUrl(
                'orob2b_customer_frontend_account_user_password_reset',
                [
                    'token' => $user->getConfirmationToken(),
                    'username' => $user->getUsername()
                ]
            ));

        $this->assertContains($resetUrl, $message->getBody());

        $crawler = $this->client->followRedirect(true);

        $this->assertEquals('Check Email', $crawler->filter('h2.title')->html());
    }
}
