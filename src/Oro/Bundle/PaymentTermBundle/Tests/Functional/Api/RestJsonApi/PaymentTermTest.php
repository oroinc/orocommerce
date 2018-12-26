<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

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

        $this->assertResponseContains('cget_payment_term.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'paymentterms', 'id' => '<toString(@payment_term_test_data_net 10->id)>']
        );

        $this->assertResponseContains('get_payment_term.yml', $response);
    }

    public function testCreate()
    {
        $data = [
            'data' => [
                'type'       => 'paymentterms',
                'attributes' => [
                    'label' => 'net 999'
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'paymentterms'],
            $data
        );

        $paymentTermId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['id'] = (string)$paymentTermId;
        $this->assertResponseContains($responseContent, $response);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getEntityManager()->find(PaymentTerm::class, $paymentTermId);
        $this->assertNotNull($paymentTerm);
        $this->assertEquals('net 999', $paymentTerm->getLabel());
    }

    public function testTryToCreateWithoutLabel()
    {
        $data = [
            'data' => [
                'type' => 'paymentterms'
            ]
        ];
        $response = $this->post(
            ['entity' => 'paymentterms'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }

    public function testUpdate()
    {
        $paymentTermId = $this->getReference('payment_term_test_data_net 10')->getId();

        $data = [
            'data' => [
                'type'       => 'paymentterms',
                'id'         => (string)$paymentTermId,
                'attributes' => [
                    'label' => 'net 11'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'paymentterms', 'id' => (string)$paymentTermId],
            $data
        );

        $this->assertResponseContains($data, $response);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getEntityManager()->find(PaymentTerm::class, $paymentTermId);
        $this->assertNotNull($paymentTerm);
        $this->assertEquals('net 11', $paymentTerm->getLabel());
    }

    public function testDelete()
    {
        $paymentTermId = $this->getReference('payment_term_test_data_net 10')->getId();

        $this->delete(
            ['entity' => 'paymentterms', 'id' => (string)$paymentTermId]
        );

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getEntityManager()->find(PaymentTerm::class, $paymentTermId);
        $this->assertNull($paymentTerm);
    }

    public function testDeleteList()
    {
        $paymentTermId = $this->getReference('payment_term_test_data_net 20')->getId();

        $this->cdelete(
            ['entity' => 'paymentterms'],
            ['filter' => ['id' => (string)$paymentTermId]]
        );

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getEntityManager()->find(PaymentTerm::class, $paymentTermId);
        $this->assertNull($paymentTerm);
    }
}
