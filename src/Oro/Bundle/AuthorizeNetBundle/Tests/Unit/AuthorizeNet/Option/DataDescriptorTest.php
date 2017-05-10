<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class DataDescriptorTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\DataDescriptor()];
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
                    'The required option "data_descriptor" is missing.',
                ],
            ],
            'wrong_type' => [
                ['data_descriptor' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "data_descriptor" with value 12345 is expected to be of type "string", but is of '.
                    'type "integer".',
                ],
            ],
            'valid' => [
                ['data_descriptor' => 'some_data_descriptor'],
                ['data_descriptor' => 'some_data_descriptor'],
            ],
        ];
    }
}
