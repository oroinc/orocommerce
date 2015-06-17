<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AccountUserControllerRegisterTest extends WebTestCase
{
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
                'email' => 'create.user@example.com',
                'plainPassword' => [
                    'first' => 'plainPassword',
                    'second' => 'plainPassword2'
                ]
            ]
        ];

        $this->client->followRedirects(true);

        $result = $this->client->getResponse();
        $this->client->submit($form, $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEmpty(
            $this->getContainer()
                ->get('doctrine')
                ->getManagerForClass('OroB2BCustomerBundle:AccountUser')
                ->getRepository('OroB2BCustomerBundle:AccountUser')
                ->findOneBy(['email' => 'create.user@example.com'])
        );

        $this->assertContains('The password fields must match.', $result->getContent());
    }

    public function testRegister()
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
                'email' => 'create.user@example.com',
                'plainPassword' => [
                    'first' => 'plainPassword',
                    'second' => 'plainPassword'
                ]
            ]
        ];

        $this->client->followRedirects(true);

        $result = $this->client->getResponse();
        $this->client->submit($form, $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertNotEmpty(
            $this->getContainer()
                ->get('doctrine')
                ->getManagerForClass('OroB2BCustomerBundle:AccountUser')
                ->getRepository('OroB2BCustomerBundle:AccountUser')
                ->findOneBy(['email' => 'create.user@example.com'])
        );
    }
}
