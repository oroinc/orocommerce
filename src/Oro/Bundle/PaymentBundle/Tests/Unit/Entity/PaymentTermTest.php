<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['label', 'net 10']
        ];

        $this->assertPropertyAccessors($this->createPaymentTerm(), $properties);
    }

    public function testToString()
    {
        $entity = new PaymentTerm();
        $this->assertEmpty((string)$entity);
        $entity->setLabel('test');
        $this->assertEquals('test', (string)$entity);
    }

    public function testRelations()
    {
        static::assertPropertyCollections($this->createPaymentTerm(), [
            ['accounts', new Account()],
            ['accountGroups', new AccountGroup()]
        ]);
    }

    /**
     * @return array
     */
    public function relationsDataProvider()
    {
        return [
            'account' => [
                'entity' => new Account(),
                'getCollectionMethod' => 'getAccounts',
                'addMethod' => 'addAccount',
                'removeMethod' => 'removeAccount',
            ],
            'accountGroup' => [
                'entity' => new AccountGroup(),
                'getCollectionMethod' => 'getAccountGroups',
                'addMethod' => 'addAccountGroup',
                'removeMethod' => 'removeAccountGroup',
            ]
        ];
    }

    /**
     * @return PaymentTerm
     */
    private function createPaymentTerm()
    {
        return new PaymentTerm();
    }
}
