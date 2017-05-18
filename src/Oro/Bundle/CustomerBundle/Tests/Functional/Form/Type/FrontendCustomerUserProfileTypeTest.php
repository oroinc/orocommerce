<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

/**
 * @dbIsolation
 */
class FrontendCustomerUserProfileTypeTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::GROUP2_EMAIL, LoadCustomerUserData::GROUP2_PASSWORD)
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadCustomerUserData::class
        ]);
    }

    public function testUserChangeEmailToAnotherUserEmail()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $form['oro_customer_frontend_customer_user_profile[email]'] = LoadCustomerUserData::EMAIL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("This value is already used", $crawler->html());
        $this->assertNotContains("Customer User profile updated", $crawler->html());

        /** @var CustomerUser $expectedUser */
        $expectedUser = $this->getReference(LoadCustomerUserData::GROUP2_EMAIL);
        $actualUsername = $this->getContainer()->get('security.token_storage')->getToken()->getUsername();

        $this->assertEquals($expectedUser->getUsername(), $actualUsername);
    }
}
