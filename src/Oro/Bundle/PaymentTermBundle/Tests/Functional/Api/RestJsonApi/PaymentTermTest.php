<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Symfony\Component\HttpFoundation\Response;

class PaymentTermTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadPaymentTermData::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'paymentterms']);

        $this->assertResponseContains('cget.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get([
            'entity' => 'paymentterms',
            'id' => '<toString(@payment_term_test_data_net 10->id)>',
        ]);

        $this->assertResponseContains('get.yml', $response);
    }

    public function testPost()
    {
        $response = $this->post(
            ['entity' => 'paymentterms'],
            'post.yml'
        );

        $responseContent = json_decode($response->getContent());

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getEntityManager()->find(PaymentTerm::class, $responseContent->data->id);

        $this->assertNotNull($paymentTerm);
        $this->assertEquals('net 999', $paymentTerm->getLabel());
    }

    public function testPatch()
    {
        $response = $this->patch(
            ['entity' => 'paymentterms', 'id' => '<toString(@payment_term_test_data_net 10->id)>'],
            'patch.yml'
        );

        $this->assertResponseContains('patch.yml', $response);
        $responseContent = json_decode($response->getContent());

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getEntityManager()->find(PaymentTerm::class, $responseContent->data->id);

        $this->assertNotNull($paymentTerm);
        $this->assertEquals('net 11', $paymentTerm->getLabel());
    }

    public function testDelete()
    {
        /** @var PaymentTerm $referencedPaymentTerm */
        $referencedPaymentTerm = $this->getReference('payment_term_test_data_net 10');
        $response = $this->delete([
            'entity' => 'paymentterms',
            'id' => $referencedPaymentTerm->getId()
        ]);

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getEntityManager()->find(PaymentTerm::class, $referencedPaymentTerm->getId());

        $this->assertNull($paymentTerm);
    }

    public function testDeleteList()
    {
        /** @var PaymentTerm $referencedPaymentTerm */
        $referencedPaymentTerm = $this->getReference('payment_term_test_data_net 20');
        $response = $this->cdelete(
            ['entity' => 'paymentterms'],
            ['filter' => ['id' => $referencedPaymentTerm->getId()]]
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getEntityManager()->find(PaymentTerm::class, $referencedPaymentTerm->getId());

        $this->assertNull($paymentTerm);
    }
}
