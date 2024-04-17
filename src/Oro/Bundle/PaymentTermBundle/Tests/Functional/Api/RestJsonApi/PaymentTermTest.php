<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;

/**
 * @dbIsolationPerTest
 */
class PaymentTermTest extends RestJsonApiTestCase
{
    use SearchExtensionTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadPaymentTermData::class]);

        self::reindex(PaymentTerm::class);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'paymentterms']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'paymentterms',
                        'id'         => '<toString(@payment_term_test_data_net 10->id)>',
                        'attributes' => ['label' => 'net 10']
                    ],
                    [
                        'type'       => 'paymentterms',
                        'id'         => '<toString(@payment_term_test_data_net 20->id)>',
                        'attributes' => ['label' => 'net 20']
                    ],
                    [
                        'type'       => 'paymentterms',
                        'id'         => '<toString(@payment_term_test_data_net 30->id)>',
                        'attributes' => ['label' => 'net 30']
                    ],
                    [
                        'type'       => 'paymentterms',
                        'id'         => '<toString(@payment_term_test_data_net 40->id)>',
                        'attributes' => ['label' => 'net 40']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithSearchTextFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'paymentterms'],
            ['filter' => ['searchText' => '30']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'paymentterms',
                        'id'         => '<toString(@payment_term_test_data_net 30->id)>',
                        'attributes' => ['label' => 'net 30']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'paymentterms', 'id' => '<toString(@payment_term_test_data_net 10->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'paymentterms',
                    'id'         => '<toString(@payment_term_test_data_net 10->id)>',
                    'attributes' => ['label' => 'net 10']
                ]
            ],
            $response
        );
    }

    public function testCreate(): void
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
        self::assertNotNull($paymentTerm);
        self::assertEquals('net 999', $paymentTerm->getLabel());
    }

    public function testTryToCreateWithoutLabel(): void
    {
        $response = $this->post(
            ['entity' => 'paymentterms'],
            ['data' => ['type' => 'paymentterms']],
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

    public function testUpdate(): void
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
        self::assertNotNull($paymentTerm);
        self::assertEquals('net 11', $paymentTerm->getLabel());
    }

    public function testDelete(): void
    {
        $toDeletePaymentTermId = $this->getReference('payment_term_test_data_net 10')->getId();
        $anotherPaymentTermId = $this->getReference('payment_term_test_data_net 20')->getId();

        $this->delete(
            ['entity' => 'paymentterms', 'id' => (string)$toDeletePaymentTermId]
        );

        self::assertNull($this->getEntityManager()->find(PaymentTerm::class, $toDeletePaymentTermId));
        self::assertNotNull($this->getEntityManager()->find(PaymentTerm::class, $anotherPaymentTermId));
    }

    public function testDeleteList(): void
    {
        $toDeletePaymentTermId = $this->getReference('payment_term_test_data_net 20')->getId();
        $anotherPaymentTermId = $this->getReference('payment_term_test_data_net 30')->getId();

        $this->cdelete(
            ['entity' => 'paymentterms'],
            ['filter' => ['id' => (string)$toDeletePaymentTermId]]
        );

        self::assertNull($this->getEntityManager()->find(PaymentTerm::class, $toDeletePaymentTermId));
        self::assertNotNull($this->getEntityManager()->find(PaymentTerm::class, $anotherPaymentTermId));
    }

    public function testDeleteListWithSearchTextFilter(): void
    {
        $toDeletePaymentTermId = $this->getReference('payment_term_test_data_net 30')->getId();
        $anotherPaymentTermId = $this->getReference('payment_term_test_data_net 40')->getId();

        $this->cdelete(
            ['entity' => 'paymentterms'],
            ['filter' => ['searchText' => '30']]
        );

        self::assertNull($this->getEntityManager()->find(PaymentTerm::class, $toDeletePaymentTermId));
        self::assertNotNull($this->getEntityManager()->find(PaymentTerm::class, $anotherPaymentTermId));
    }
}
