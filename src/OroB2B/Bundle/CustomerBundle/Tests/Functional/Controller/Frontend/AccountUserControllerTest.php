<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testViewProfile()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_profile'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(LoadAccountUserData::AUTH_USER, $crawler->filter('.customer-content')->html());
    }

    public function testEditProfile()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form();
        $submittedData = [
            'orob2b_customer_frontend_account_user' => [
                '_token' => $form->get('orob2b_customer_frontend_account_user[_token]')->getValue(),
                'middleName' => 'AccountUserUpdated'
            ]
        ];

        $this->client->followRedirects(true);

        $result = $this->client->getResponse();
        $this->client->submit($form, $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Account User profile updated', $html);
        $this->assertContains('AccountUserUpdated', $html);
    }

    public function testEditProfilePasswordMismatch()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'orob2b_customer_frontend_account_user' => [
                '_token' => $form->get('orob2b_customer_frontend_account_user[_token]')->getValue(),
                'changePassword' => [
                    'currentPassword' => LoadAccountUserData::AUTH_PW,
                    'plainPassword' => [
                        'first' => '123456',
                        'second' => '654321',
                    ]
                ]
            ]
        ];

        $this->client->followRedirects(true);

        $result = $this->client->getResponse();
        $this->client->submit($form, $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('The password fields must match.', $html);
        $this->assertNotContains('This value should be the user current password.', $html);
    }

    public function testEditProfileWithoutCurrentPassword()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_frontend_account_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'orob2b_customer_frontend_account_user' => [
                '_token' => $form->get('orob2b_customer_frontend_account_user[_token]')->getValue(),
                'changePassword' => [
                    'currentPassword' => '123456',
                    'plainPassword' => [
                        'first' => '123456',
                        'second' => '123456',
                    ]
                ]
            ]
        ];

        $this->client->followRedirects(true);

        $result = $this->client->getResponse();
        $this->client->submit($form, $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('This value should be the user current password.', $html);
        $this->assertNotContains('The password fields must match.', $html);
    }
}
