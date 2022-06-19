<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes;

/**
 * @dbIsolationPerTest
 */
class CustomerTaxCodeAssociationTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadCustomerTaxCodes::class]);
    }

    public function testGetCustomer(): void
    {
        $response = $this->get(
            ['entity' => 'customers', 'id' => '<toString(@customer.level_1_1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'customers',
                    'id'            => '<toString(@customer.level_1_1->id)>',
                    'relationships' => [
                        'taxCode' => [
                            'data' => [
                                'type' => 'customertaxcodes',
                                'id'   => '<toString(@customer_tax_code.TAX3->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateCustomer(): void
    {
        $customerId = $this->getReference('customer.level_1_1')->getId();
        $customerTaxCodeId = $this->getReference('customer_tax_code.TAX2')->getId();
        $data = [
            'data' => [
                'type'          => 'customers',
                'id'            => (string)$customerId,
                'relationships' => [
                    'taxCode' => [
                        'data' => [
                            'type' => 'customertaxcodes',
                            'id'   => (string)$customerTaxCodeId
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'customers', 'id' => '<toString(@customer.level_1_1->id)>'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $customer = $this->getEntityManager()->find(Customer::class, $customerId);
        self::assertEquals($customerTaxCodeId, $customer->getTaxCode()->getId());
    }
}
