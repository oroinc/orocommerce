<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;

/**
 * @dbIsolation
 */
class AccountUserRoleControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_account_account_user_role_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $accountUserRoleLabel = $this->getReference(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER);
        $response = $this->requestFrontendGrid(
            'frontend-account-account-user-roles-grid',
            [
                'frontend-account-account-user-roles-grid[_filter][label][value]' => $accountUserRoleLabel
            ]
        );

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertContains(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER, $response->getContent());
    }
}
