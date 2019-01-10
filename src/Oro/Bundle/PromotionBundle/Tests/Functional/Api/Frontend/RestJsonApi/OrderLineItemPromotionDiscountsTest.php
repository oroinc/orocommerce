<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Brick\Math\BigDecimal;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadOrderPromotionalDiscountsData;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\DBAL\Types\MoneyType;

class OrderLineItemPromotionDiscountsTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadOrderPromotionalDiscountsData::class
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
    private function getDiscountRelatedFields($price, $quantity, $discountAmount, ?Result $taxValue)
    {
        if (null === $discountAmount && null === $taxValue) {
            return [
                'rowTotalDiscountAmount'            => $this->getMoneyValue(0.0),
                'rowTotalAfterDiscount'             => $this->getMoneyValue(
                    $price * $quantity
                ),
                'rowTotalAfterDiscountIncludingTax' => $this->getMoneyValue(0.0),
                'rowTotalAfterDiscountExcludingTax' => $this->getMoneyValue(0.0)
            ];
        }

        return [
            'rowTotalDiscountAmount'            => $this->getMoneyValue($discountAmount),
            'rowTotalAfterDiscount'             => $this->getMoneyValue(
                $price * $quantity - $discountAmount
            ),
            'rowTotalAfterDiscountIncludingTax' => $this->getMoneyValue(
                $taxValue->getRow()->getIncludingTax() - $discountAmount
            ),
            'rowTotalAfterDiscountExcludingTax' => $this->getMoneyValue(
                $taxValue->getRow()->getExcludingTax() - $discountAmount
            )
        ];
    }

    public function testGetListShouldReturnDiscountRelatedFields()
    {
        /** @var OrderLineItem $lineItem1 */
        $lineItem1 = $this->getReference('order1_line_item1');
        $lineItem1Price = $lineItem1->getValue();
        $lineItem1Quantity = $lineItem1->getQuantity();
        $lineItem1DiscountAmount =
            $this->getReference('order1_line_item1_discount1')->getAmount()
            + $this->getReference('order1_line_item1_discount2')->getAmount();
        /** @var Result $lineItem1TaxValue */
        $lineItem1TaxValue = $this->getReference('order1_line_item1_tax_value')->getResult();

        /** @var OrderLineItem $lineItem2 */
        $lineItem2 = $this->getReference('order1_line_item2');
        $lineItem2Price = $lineItem2->getValue();
        $lineItem2Quantity = $lineItem2->getQuantity();

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
                        'attributes' => $this->getDiscountRelatedFields(
                            $lineItem1Price,
                            $lineItem1Quantity,
                            $lineItem1DiscountAmount,
                            $lineItem1TaxValue
                        )
                    ],
                    [
                        'type'       => 'orderlineitems',
                        'id'         => '<toString(@order1_line_item2->id)>',
                        'attributes' => $this->getDiscountRelatedFields(
                            $lineItem2Price,
                            $lineItem2Quantity,
                            null,
                            null
                        )
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetShouldReturnDiscountRelatedFields()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order1_line_item1');
        $lineItemPrice = $lineItem->getValue();
        $lineItemQuantity = $lineItem->getQuantity();
        $lineItemDiscountAmount =
            $this->getReference('order1_line_item1_discount1')->getAmount()
            + $this->getReference('order1_line_item1_discount2')->getAmount();
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
                    'attributes' => $this->getDiscountRelatedFields(
                        $lineItemPrice,
                        $lineItemQuantity,
                        $lineItemDiscountAmount,
                        $lineItemTaxValue
                    )
                ]
            ],
            $response
        );
    }

    public function testGetShouldEmptyValuesIfLineItemDoesNotHaveDiscounts()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order1_line_item2');
        $lineItemPrice = $lineItem->getValue();
        $lineItemQuantity = $lineItem->getQuantity();

        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderlineitems',
                    'id'         => '<toString(@order1_line_item2->id)>',
                    'attributes' => $this->getDiscountRelatedFields(
                        $lineItemPrice,
                        $lineItemQuantity,
                        null,
                        null
                    )
                ]
            ],
            $response
        );
    }

    public function testGetShouldComputeDiscountRelatedFieldsEvenIfTaxFieldsWereNotRequested()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order1_line_item1');
        $lineItemPrice = $lineItem->getValue();
        $lineItemQuantity = $lineItem->getQuantity();
        $lineItemDiscountAmount =
            $this->getReference('order1_line_item1_discount1')->getAmount()
            + $this->getReference('order1_line_item1_discount2')->getAmount();
        /** @var Result $lineItemTaxValue */
        $lineItemTaxValue = $this->getReference('order1_line_item1_tax_value')->getResult();

        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
            [
                'fields[orderlineitems]' => implode(
                    ',',
                    [
                        'rowTotalDiscountAmount',
                        'rowTotalAfterDiscount',
                        'rowTotalAfterDiscountIncludingTax',
                        'rowTotalAfterDiscountExcludingTax'
                    ]
                )
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderlineitems',
                    'id'         => '<toString(@order1_line_item1->id)>',
                    'attributes' => $this->getDiscountRelatedFields(
                        $lineItemPrice,
                        $lineItemQuantity,
                        $lineItemDiscountAmount,
                        $lineItemTaxValue
                    )
                ]
            ],
            $response
        );
    }
}
