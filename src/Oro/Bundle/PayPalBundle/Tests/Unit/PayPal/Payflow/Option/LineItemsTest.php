<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\LineItems;

class LineItemsTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new LineItems()];
    }

    public function testPrepareOptions()
    {
        $result = LineItems::prepareOptions([
            [
                'L_NAME%d' => 'name',
                'L_DESC%d' => 'description',
                'L_COST%d' => 10,
                'L_QTY%d' => 5,
            ],            [
                'L_NAME%d' => 'name1',
                'L_DESC%d' => 'description1',
                'L_COST%d' => 10,
                'L_QTY%d' => 7,
            ],
        ]);

        $this->assertEquals([
                'L_NAME1' => 'name',
                'L_DESC1' => 'description',
                'L_COST1' => 10,
                'L_QTY1' => 5,
                'L_NAME2' => 'name1',
                'L_DESC2' => 'description1',
                'L_COST2' => 10,
                'L_QTY2' => 7,
                'ITEMAMT' => (5 * 10) + (7 * 10)
        ], $result);
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid L_NAMEn length' => [
                [
                    'L_NAME1' => str_repeat('a', 50),
                    'L_DESC1' => 'Description',
                    'L_COST1' => 10.5,
                    'L_QTY1' => 10,
                ],
                [
                    'L_NAME1' => str_repeat('a', 36),
                    'L_DESC1' => 'Description',
                    'L_COST1' => '10.50',
                    'L_QTY1' => 10,
                ]
            ],
            'invalid L_DESCn length' => [
                [
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => str_repeat('a', 50),
                    'L_COST1' => 10.5,
                    'L_QTY1' => 10,
                ],
                [
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => str_repeat('a', 35),
                    'L_COST1' => '10.50',
                    'L_QTY1' => 10,
                ]
            ],
            'invalid L_NAMEn type' => [
                [
                    'L_NAME1' => 123,
                    'L_DESC1' => 'Description',
                    'L_COST1' => 10.5,
                    'L_QTY1' => 10,
                ],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "L_NAME1" with value 123 is expected to be of type "string", but is of type "integer".'
                ],
            ],
            'invalid L_DESCn type' => [
                [
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 123,
                    'L_COST1' => 10.5,
                    'L_QTY1' => 10,
                ],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "L_DESC1" with value 123 is expected to be of type "string", but is of type "integer".'
                ],
            ],
            'invalid L_QTYn type' => [
                [
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 'Description',
                    'L_COST1' => 10.5,
                    'L_QTY1' => 0.5,
                ],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "L_QTY1" with value 0.5 is expected to be of type "integer", but is of type "double".'
                ],
            ],
            'valid' => [
                [
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 'Description',
                    'L_COST1' => 10.5,
                    'L_QTY1' => 10,
                ],
                [
                    'L_NAME1' => 'ProductName',
                    'L_DESC1' => 'Description',
                    'L_COST1' => '10.50',
                    'L_QTY1' => 10,
                ],
            ],
        ];
    }
}
