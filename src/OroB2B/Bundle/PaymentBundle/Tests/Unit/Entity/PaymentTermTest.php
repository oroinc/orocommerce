<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermTest extends EntityTestCase
{
    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['label', 'net 10']
        ];

        $this->assertPropertyAccessors(new PaymentTerm(), $properties);
    }

    public function testToString()
    {
        $entity = new PaymentTerm();
        $this->assertEmpty((string)$entity);
        $entity->setLabel('test');
        $this->assertEquals('test', (string)$entity);
    }

    /**
     * @dataProvider relationsDataProvider
     *
     * @param Account|AccountGroup $entity
     * @param string $getCollectionMethod
     * @param string $addMethod
     * @param string $removeMethod
     */
    public function testRelations($entity, $getCollectionMethod, $addMethod, $removeMethod)
    {
        $paymentTerm = $this->createPaymentTerm();

        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $paymentTerm->$getCollectionMethod()
        );
        $this->assertCount(0, $paymentTerm->$getCollectionMethod());

        $this->assertInstanceOf(
            'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm',
            $paymentTerm->$addMethod($entity)
        );
        $this->assertCount(1, $paymentTerm->$getCollectionMethod());

        $paymentTerm->$addMethod($entity);
        $this->assertCount(1, $paymentTerm->$getCollectionMethod());

        $paymentTerm->$removeMethod($entity);
        $this->assertCount(0, $paymentTerm->$getCollectionMethod());
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
