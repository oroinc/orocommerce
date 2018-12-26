<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

class CustomerPaymentTermTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadPaymentTermData::class]);
    }

    public function testPaymentTermIsReturnedInCustomerGetListResponse()
    {
        $response = $this->cget(
            ['entity' => 'customers'],
            ['filter[id]' => '<toString(@customer.level_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'customers',
                        'id'            => '<toString(@customer.level_1->id)>',
                        'relationships' => [
                            'paymentTerm' => [
                                'data' => [
                                    'type' => 'paymentterms',
                                    'id'   => '<toString(@payment_term_test_data_net 10->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCustomerGetResponseContainsAssignedPaymentTerm()
    {
        $response = $this->get([
            'entity' => 'customers',
            'id'     => '<toString(@customer.level_1->id)>'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'customers',
                    'id'            => '<toString(@customer.level_1->id)>',
                    'relationships' => [
                        'paymentTerm' => [
                            'data' => [
                                'type' => 'paymentterms',
                                'id'   => '<toString(@payment_term_test_data_net 10->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCustomerPaymentTermCanBeModified()
    {
        $customerId = $this->getReference('customer.level_1')->getId();

        $data = [
            'data' => [
                'type'          => 'customers',
                'id'            => (string)$customerId,
                'relationships' => [
                    'paymentTerm' => [
                        'data' => [
                            'type' => 'paymentterms',
                            'id'   => '<toString(@payment_term_test_data_net 20->id)>'
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'customers', 'id' => (string)$customerId],
            $data
        );

        $this->assertResponseContains($data, $response);

        /** @var Customer $customer */
        $customer = $this->getEntityManager()->find(Customer::class, $customerId);
        $this->assertNotNull($customer);

        $paymentTermProvider = self::getContainer()->get('oro_payment_term.provider.payment_term');
        $paymentTerm = $paymentTermProvider->getCustomerPaymentTerm($customer);
        $this->assertNotNull($paymentTerm);
        $this->assertEquals(LoadPaymentTermData::TERM_LABEL_NET_20, $paymentTerm->getLabel());
    }
}
