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
        $this->client->useHashNavigation(true);
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

        $this->assertDeleteOperation(
            $account->getId(),
            'oro_customer.entity.account.class',
            'oro_customer_account_index'
        );
    }
}
