<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TaxBundle\Entity\Tax;

/**
 * @dbIsolationPerTest
 */
class TaxTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroTaxBundle/Tests/Functional/Api/DataFixtures/tax.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'taxes']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'taxes',
                        'id'         => '<toString(@tax_1->id)>',
                        'attributes' => [
                            'code'        => 'TAX_1',
                            'description' => 'Tax 1',
                            'rate'        => 0.1,
                            'createdAt'   => '@tax_1->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'taxes', 'id' => '<toString(@tax_1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'taxes',
                    'id'         => '<toString(@tax_1->id)>',
                    'attributes' => [
                        'code'        => 'TAX_1',
                        'description' => 'Tax 1',
                        'rate'        => 0.1,
                        'createdAt'   => '@tax_1->createdAt->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt'   => '@tax_1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                    ]
                ]
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $taxId = $this->getReference('tax_1')->getId();

        $this->delete(
            ['entity' => 'taxes', 'id' => (string)$taxId]
        );

        $tax = $this->getEntityManager()->find(Tax::class, $taxId);
        self::assertTrue(null === $tax);
    }

    public function testDeleteList(): void
    {
        $taxId = $this->getReference('tax_1')->getId();

        $this->cdelete(
            ['entity' => 'taxes'],
            ['filter[id]' => (string)$taxId]
        );

        $tax = $this->getEntityManager()->find(Tax::class, $taxId);
        self::assertTrue(null === $tax);
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type'       => 'taxes',
                'attributes' => [
                    'code'        => 'NEW_TAX',
                    'description' => 'new tax',
                    'rate'        => 0.1
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'taxes'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $tax = $this->getEntityManager()->find(Tax::class, $this->getResourceId($response));
        self::assertNotNull($tax);
        self::assertEquals('NEW_TAX', $tax->getCode());
        self::assertEquals('new tax', $tax->getDescription());
        self::assertEquals(0.1, $tax->getRate());
    }

    public function testUpdate(): void
    {
        $taxId = $this->getReference('tax_1')->getId();

        $this->patch(
            ['entity' => 'taxes', 'id' => (string)$taxId],
            [
                'data' => [
                    'type'       => 'taxes',
                    'id'         => (string)$taxId,
                    'attributes' => [
                        'description' => 'updated tax'
                    ]
                ]
            ]
        );

        $tax = $this->getEntityManager()->find(Tax::class, $taxId);
        self::assertNotNull($tax);
        self::assertEquals('updated tax', $tax->getDescription());
    }
}
