<?php

namespace OroB2B\Bundle\UserBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\FrontendBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RegistrationControllerTest extends WebTestCase
{
    const FIRST_NAME         = 'John';
    const LAST_NAME          = 'Doe';
    const UPDATED_FIRST_NAME = 'Jim';
    const UPDATED_LAST_NAME  = 'Brown';
    const EMAIL              = 'johndoe@example.com';
    const UPDATED_EMAIL      = 'jimbrown@example.com';
    const PASSWORD           = '123456789';
    const INVALID_PASSWORD   = '654321';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * Test valid submit
     */
    public function testRegister()
    {
        // Register user
        $crawler = $this->client->request('GET', $this->getUrl('fos_user_registration_register'));

        $form = $crawler->selectButton('Register')->form([
            'fos_user_registration_form[firstName]'             => self::FIRST_NAME,
            'fos_user_registration_form[lastName]'              => self::LAST_NAME,
            'fos_user_registration_form[email]'                 => self::EMAIL,
            'fos_user_registration_form[plainPassword][first]'  => self::PASSWORD,
            'fos_user_registration_form[plainPassword][second]' => self::PASSWORD,
        ]);

        $this->client->followRedirects(false);
        $this->client->submit($form);

        // Collect messages
        /** @var array $collectedMessages */
        $collectedMessages = $this->client->getProfile()->getCollector('swiftmailer')->getMessages();

        $crawler = $this->client->followRedirect();

        // Test email notification
        /** @var \Swift_Message $message */
        $message = reset($collectedMessages);

        $this->assertEquals(self::EMAIL, key($message->getTo()));
        $this->assertEquals('Welcome '. self::EMAIL .'!', $message->getSubject());

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('The user has been created successfully', $crawler->html());

        $this->enabledUser(self::EMAIL);
    }

    /**
     * Test invalid login data
     */
    public function testInvalidLogin()
    {
        $crawler = $this->client->request('GET', $this->getUrl('fos_user_security_login'));

        // Invalid email or password
        $form = $crawler->selectButton('Login')->form([
            '_username' => self::EMAIL,
            '_password' => self::INVALID_PASSWORD
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Invalid email or password', $crawler->html());
    }

    public function testValidLogin()
    {
        $crawler = $this->client->request('GET', $this->getUrl('fos_user_security_login'));

        // Valid login data
        $form = $crawler->selectButton('Login')->form([
            '_username' => self::EMAIL,
            '_password' => self::PASSWORD
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Logged in as '. self::EMAIL, $crawler->html());
    }

    /**
     * Reset user password
     * @depends testRegister
     */
    public function testResetPassword()
    {
        $crawler = $this->client->request('GET', $this->getUrl('fos_user_resetting_request'));

        $form = $crawler->selectButton('Reset password')->form([
            'username' => self::EMAIL,
        ]);

        $this->client->followRedirects(false);
        $this->client->submit($form);

        // Collect messages
        /** @var array $collectedMessages */
        $collectedMessages = $this->client->getProfile()->getCollector('swiftmailer')->getMessages();

        $crawler = $this->client->followRedirect();

        // Test email notification
        /** @var \Swift_Message $message */
        $message = reset($collectedMessages);

        $this->assertEquals(self::EMAIL, key($message->getTo()));
        $this->assertEquals('Reset Password', $message->getSubject());

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(
            'It contains a link you must click to reset your password.',
            $crawler->html()
        );
    }

    /**
     * test profile
     */
    public function testProfile()
    {
        // Visit profile
        $crawler = $this->client->request('GET', $this->getUrl('fos_user_profile_show'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('FirstName: '. self::FIRST_NAME, $crawler->html());
        $this->assertContains('LastName: '. self::LAST_NAME, $crawler->html());
        $this->assertContains('Email: '. self::EMAIL, $crawler->html());

        // Click edit button and change user data
        $link = $crawler->filter('a:contains("Edit profile")')->link();
        $crawler = $this->client->click($link);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Update')->form([
            'fos_user_profile_form[firstName]'        => self::UPDATED_FIRST_NAME,
            'fos_user_profile_form[lastName]'         => self::UPDATED_LAST_NAME,
            'fos_user_profile_form[email]'            => self::UPDATED_EMAIL,
            'fos_user_profile_form[current_password]' => self::PASSWORD,
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('The profile has been updated', $crawler->html());
        $this->assertContains('FirstName: '. self::UPDATED_FIRST_NAME, $crawler->html());
        $this->assertContains('LastName: '. self::UPDATED_LAST_NAME, $crawler->html());
        $this->assertContains('Email: '. self::UPDATED_EMAIL, $crawler->html());

        // Logout
        $this->client->request('GET', $this->getUrl('fos_user_security_logout'));
    }

    /**
     * @param $email
     */
    protected function enabledUser($email)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository('OroB2BUserBundle:User')->findOneBy(['email' => $email]);

        $this->assertNotNull($user);

        $user->setEnabled(true);

        $em->persist($user);
        $em->flush($user);
    }
}
