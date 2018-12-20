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
        $response = $this->cget(['entity' => $this->getEntityType(Customer::class)]);
        $responseContent = $response->getContent();

        $this->assertContains('paymentTerm', $responseContent);
    }

    public function testCustomerGetResponseContainsAssignedPaymentTerm()
    {
        $response = $this->get([
            'entity' => $this->getEntityType(Customer::class),
            'id' => '<toString(@customer.level_1->id)>'
        ]);

        $this->assertResponseContains('customer/get.yml', $response);
    }

    public function testCustomerPaymentTermCanBeModified()
    {
        $response = $this->patch(
            ['entity' => $this->getEntityType(Customer::class), 'id' => '<toString(@customer.level_1->id)>'],
            'customer/patch.yml'
        );

        $this->assertResponseContains('customer/patch.yml', $response);
        $responseContent = json_decode($response->getContent());

        /** @var Customer $customer */
        $customer = $this->getEntityManager()->find(Customer::class, $responseContent->data->id);

        $this->assertNotNull($customer);

        $paymentTermProvider = $this->getContainer()->get('oro_payment_term.provider.payment_term');
        $paymentTerm = $paymentTermProvider->getCustomerPaymentTerm($customer);

        $this->assertNotNull($paymentTerm);
        $this->assertEquals(LoadPaymentTermData::TERM_LABEL_NET_20, $paymentTerm->getLabel());
    }
}
