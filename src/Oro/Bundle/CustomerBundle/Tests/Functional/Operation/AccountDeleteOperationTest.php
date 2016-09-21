<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\CustomerBundle\Entity\Account;

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
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Account $account */
        $account = $this->getReference('account.orphan');

        $this->assertDeleteOperation($account->getId(), 'oro_account.entity.account.class', 'oro_account_index');
    }
}
