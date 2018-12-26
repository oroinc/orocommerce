<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Brick\Math\BigDecimal;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadOrderTaxesData;
use Oro\DBAL\Types\MoneyType;

class OrderTaxesTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadOrderTaxesData::class
        ]);
    }

    /**
     * @param mixed $value
     *
     * @return string|null
     */
    private function getMoneyValue($value)
    {
        if (null !== $value) {
            $value = (string)BigDecimal::of($value)->toScale(MoneyType::TYPE_SCALE);
        }

        return $value;
    }

    /**
     * @param Result|null $taxValue
     *
     * @return array
     */
    private function getTaxRelatedFields(?Result $taxValue)
    {
        if (null === $taxValue) {
            return [
                'totalIncludingTax' => null,
                'totalExcludingTax' => null,
                'totalTaxAmount'    => null
            ];
        }

        return [
            'totalIncludingTax' => $this->getMoneyValue(
                $taxValue->getTotal()->getIncludingTax()
            ),
            'totalExcludingTax' => $this->getMoneyValue(
                $taxValue->getTotal()->getExcludingTax()
            ),
            'totalTaxAmount'    => $this->getMoneyValue(
                $taxValue->getTotal()->getTaxAmount()
            )
        ];
    }

    public function testGetListShouldReturnTaxRelatedFields()
    {
        /** @var Result $order1TaxValue */
        $order1TaxValue = $this->getReference('order1_tax_value')->getResult();
        /** @var Result $order2TaxValue */
        $order2TaxValue = $this->getReference('order2_tax_value')->getResult();

        $response = $this->cget(
            ['entity' => 'orders']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order1->id)>',
                        'attributes' => $this->getTaxRelatedFields($order1TaxValue)
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order2->id)>',
                        'attributes' => $this->getTaxRelatedFields($order2TaxValue)
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order3->id)>',
                        'attributes' => $this->getTaxRelatedFields(null)
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetShouldReturnTaxRelatedFields()
    {
        /** @var Result $orderTaxValue */
        $orderTaxValue = $this->getReference('order1_tax_value')->getResult();

        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => '<toString(@order1->id)>',
                    'attributes' => $this->getTaxRelatedFields($orderTaxValue)
                ]
            ],
            $response
        );
    }

    public function testGetShouldEmptyValuesIfOrderDoesNotHaveTaxValue()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => '<toString(@order3->id)>',
                    'attributes' => $this->getTaxRelatedFields(null)
                ]
            ],
            $response
        );
    }
}
