<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
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

    /**
     * @dataProvider relationsDataProvider
     *
     * @param Customer|CustomerGroup $entity
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
            'customer' => [
                'entity' => new Customer(),
                'getCollectionMethod' => 'getCustomers',
                'addMethod' => 'addCustomer',
                'removeMethod' => 'removeCustomer',
            ],
            'customerGroup' => [
                'entity' => new CustomerGroup(),
                'getCollectionMethod' => 'getCustomerGroups',
                'addMethod' => 'addCustomerGroup',
                'removeMethod' => 'removeCustomerGroup',
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
