<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;

/**
 * @dbIsolation
 */
class AccountDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

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

        $this->assertExecuteOperation(
            'DELETE',
            $accountId,
            $this->getContainer()->getParameter('orob2b_account.entity.account.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_account_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
