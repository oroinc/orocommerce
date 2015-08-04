<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;

/**
 * @dbIsolation
 */
class AccountControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Account $account */
        $account = $this->getReference('account.orphan');
        $accountId = $account->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_account_delete_account', ['id' => $accountId])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
