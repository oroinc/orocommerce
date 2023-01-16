<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class LineItemsTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\LineItems(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'valid with action SET_EC' => [
                [
                    ECOption\Action::ACTION => ECOption\Action::SET_EC,
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 'Description',
                    'L_COST1' => 10.5,
                    'L_QTY1' => 10,
                ],
                [
                    ECOption\Action::ACTION => ECOption\Action::SET_EC,
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 'Description',
                    'L_COST1' => '10.50',
                    'L_QTY1' => 10,
                ],
            ],
            'valid with action DO_EC' => [
                [
                    ECOption\Action::ACTION => ECOption\Action::DO_EC,
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 'Description',
                    'L_COST1' => 10.5,
                    'L_QTY1' => 10,
                ],
                [
                    ECOption\Action::ACTION => ECOption\Action::DO_EC,
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 'Description',
                    'L_COST1' => '10.50',
                    'L_QTY1' => 10,
                ],
            ],
            'invalid with action GET_EC_DETAILS' => [
                [
                    ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS,
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 'Description',
                    'L_COST1' => 10.5,
                    'L_QTY1' => 10,
                ],
                [],
                [
                    UndefinedOptionsException::class,
                    'The options "L_COST1", "L_DESC1", "L_NAME1", "L_QTY1" do not exist. Defined options are: "ACTION".'
                ]
            ],
        ];
    }
}
