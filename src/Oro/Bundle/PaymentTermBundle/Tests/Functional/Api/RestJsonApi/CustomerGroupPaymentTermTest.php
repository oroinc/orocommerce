<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

class CustomerGroupPaymentTermTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadPaymentTermData::class]);
    }

    public function testPaymentTermIsReturnedInCustomerGroupGetListResponse()
    {
        $response = $this->cget(
            ['entity' => 'customergroups'],
            ['filter[id]' => '<toString(@customer_group.group1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'customergroups',
                        'id'            => '<toString(@customer_group.group1->id)>',
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

    public function testCustomerGroupGetResponseContainsAssignedPaymentTerm()
    {
        $response = $this->get(
            ['entity' => 'customergroups', 'id' => '<toString(@customer_group.group1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'customergroups',
                    'id'            => '<toString(@customer_group.group1->id)>',
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

    public function testCustomerGroupPaymentTermCanBeModified()
    {
        $customerGroupId = $this->getReference('customer_group.group1')->getId();

        $data = [
            'data' => [
                'type'          => 'customergroups',
                'id'            => (string)$customerGroupId,
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
            ['entity' => 'customergroups', 'id' => (string)$customerGroupId],
            $data
        );

        $this->assertResponseContains($data, $response);

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntityManager()->find(CustomerGroup::class, $customerGroupId);
        $this->assertNotNull($customerGroup);

        $paymentTermProvider = self::getContainer()->get('oro_payment_term.provider.payment_term');
        $paymentTerm = $paymentTermProvider->getCustomerGroupPaymentTerm($customerGroup);
        $this->assertNotNull($paymentTerm);
        $this->assertEquals(LoadPaymentTermData::TERM_LABEL_NET_20, $paymentTerm->getLabel());
    }
}
