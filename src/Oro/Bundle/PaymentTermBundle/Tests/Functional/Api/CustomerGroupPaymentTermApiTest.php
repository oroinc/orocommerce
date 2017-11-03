<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

class CustomerGroupPaymentTermApiTest extends RestJsonApiTestCase
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
        $associationName = ExtendHelper::buildAssociationName(PaymentTerm::class);

        $response = $this->cget(['entity' => $this->getEntityType(CustomerGroup::class)]);
        $responseContent = $response->getContent();

        $this->assertContains($associationName, $responseContent);
    }

    public function testCustomerGroupGetResponseContainsAssignedPaymentTerm()
    {
        $response = $this->get([
            'entity' => $this->getEntityType(CustomerGroup::class),
            'id' => '<toString(@customer_group.group1->id)>'
        ]);

        $this->assertResponseContains('customer_group/get.yml', $response);
    }

    public function testCustomerGroupPaymentTermCanBeModified()
    {
        $response = $this->patch(
            ['entity' => $this->getEntityType(CustomerGroup::class), 'id' => '<toString(@customer_group.group1->id)>'],
            'customer_group/patch.yml'
        );

        $this->assertResponseContains('customer_group/patch.yml', $response);
        $responseContent = json_decode($response->getContent());

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntityManager()->find(CustomerGroup::class, $responseContent->data->id);

        $this->assertNotNull($customerGroup);

        $paymentTermProvider = $this->getContainer()->get('oro_payment_term.provider.payment_term');
        $paymentTerm = $paymentTermProvider->getCustomerGroupPaymentTerm($customerGroup);

        $this->assertNotNull($paymentTerm);
        $this->assertEquals(LoadPaymentTermData::TERM_LABEL_NET_20, $paymentTerm->getLabel());
    }
}
