<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

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
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_account_account_user_role_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // TODO: check role name and role type after testCreate method
    }
}
