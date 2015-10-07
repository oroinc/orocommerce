<?php

namespace OroB2B\src\OroB2B\Bundle\CMSBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;
use OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadLoginPage;

/**
 * @dbIsolation
 */
class LoginPageControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadLoginPage'
            ]
        );
    }

    public function testDelete()
    {
        /** @var LoginPage $loginPage */
        $loginPage = $this->getReference(LoadLoginPage::LOGIN_PAGE_UNIQUE_REFERENCE);

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_cms_delete_login_page', ['id' => $loginPage->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
