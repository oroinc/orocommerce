<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Brick\Math\BigDecimal;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadOrderTaxesData;
use Oro\DBAL\Types\MoneyType;

class OrderLineItemTaxesTest extends FrontendRestJsonApiTestCase
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
                'unitPriceIncludingTax' => null,
                'unitPriceExcludingTax' => null,
                'unitPriceTaxAmount'    => null,
                'rowTotalIncludingTax'  => null,
                'rowTotalExcludingTax'  => null,
                'rowTotalTaxAmount'     => null,
                'taxes'                 => []
            ];
        }

        return [
            'unitPriceIncludingTax' => $this->getMoneyValue(
                $taxValue->getUnit()->getIncludingTax()
            ),
            'unitPriceExcludingTax' => $this->getMoneyValue(
                $taxValue->getUnit()->getExcludingTax()
            ),
            'unitPriceTaxAmount'    => $this->getMoneyValue(
                $taxValue->getUnit()->getTaxAmount()
            ),
            'rowTotalIncludingTax'  => $this->getMoneyValue(
                $taxValue->getRow()->getIncludingTax()
            ),
            'rowTotalExcludingTax'  => $this->getMoneyValue(
                $taxValue->getRow()->getExcludingTax()
            ),
            'rowTotalTaxAmount'     => $this->getMoneyValue(
                $taxValue->getRow()->getTaxAmount()
            ),
            'taxes'                 => array_map(
                function (TaxResultElement $item) {
                    return [
                        TaxResultElement::TAX            => $item->getTax(),
                        TaxResultElement::RATE           => $item->getRate(),
                        TaxResultElement::TAXABLE_AMOUNT => $item->getTaxableAmount(),
                        TaxResultElement::TAX_AMOUNT     => $item->getTaxAmount(),
                        TaxResultElement::CURRENCY       => $item->getCurrency()
                    ];
                },
                $taxValue->getTaxes()
            )
        ];
    }

    public function testGetListShouldReturnTaxRelatedFields()
    {
        /** @var Result $lineItem1TaxValue */
        $lineItem1TaxValue = $this->getReference('order1_line_item1_tax_value')->getResult();

        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            ['filter[order]' => '<toString(@order1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orderlineitems',
                        'id'         => '<toString(@order1_line_item1->id)>',
                        'attributes' => $this->getTaxRelatedFields($lineItem1TaxValue)
                    ],
                    [
                        'type'       => 'orderlineitems',
                        'id'         => '<toString(@order1_line_item2->id)>',
                        'attributes' => $this->getTaxRelatedFields(null)
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetShouldReturnTaxRelatedFields()
    {
        /** @var Result $lineItemTaxValue */
        $lineItemTaxValue = $this->getReference('order1_line_item1_tax_value')->getResult();

        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderlineitems',
                    'id'         => '<toString(@order1_line_item1->id)>',
                    'attributes' => $this->getTaxRelatedFields($lineItemTaxValue)
                ]
            ],
            $response
        );
    }

    public function testGetShouldEmptyValuesIfLineItemDoesNotHaveTaxValue()
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderlineitems',
                    'id'         => '<toString(@order1_line_item2->id)>',
                    'attributes' => $this->getTaxRelatedFields(null)
                ]
            ],
            $response
        );
    }
}
