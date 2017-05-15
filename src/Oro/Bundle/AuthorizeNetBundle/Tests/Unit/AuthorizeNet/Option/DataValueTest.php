<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class DataValueTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\DataValue()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'required' => [
                [],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "data_value" is missing.',
                ],
            ],
            'wrong_type' => [
                ['data_value' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "data_value" with value 12345 is expected to be of type "string", but is of '.
                    'type "integer".',
                ],
            ],
            'valid' => [
                ['data_value' => 'some_data_value'],
                ['data_value' => 'some_data_value'],
            ],
        ];
    }
}
