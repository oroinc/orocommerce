<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserProfileControllerTest extends WebTestCase
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
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_profile'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(LoadAccountUserData::AUTH_USER, $crawler->filter('.user-page')->html());
    }

    public function testEditProfile()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form->offsetSet('orob2b_account_frontend_account_user_profile[firstName]', 'AccountUserUpdated');

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('AccountUserUpdated', $crawler->filter('.user-page')->html());
    }

    public function testEditProfilePasswordMismatch()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form->offsetSet(
            'orob2b_account_frontend_account_user_profile[changePassword]',
            [
                'currentPassword' => LoadAccountUserData::AUTH_PW,
                'plainPassword' => [
                    'first' => '123456',
                    'second' => '654321',
                ]
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('The password fields must match.', $crawler->filter('.password_first span')->html());
    }

    public function testEditProfileWithoutCurrentPassword()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form->offsetSet(
            'orob2b_account_frontend_account_user_profile[changePassword]',
            [
                'currentPassword' => '123456',
                'plainPassword' => [
                    'first' => '123456',
                    'second' => '123456',
                ]
            ]
        );
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(
            'This value should be the user\'s current password.',
            $crawler->filter('.current_password span')->html()
        );
    }
}
