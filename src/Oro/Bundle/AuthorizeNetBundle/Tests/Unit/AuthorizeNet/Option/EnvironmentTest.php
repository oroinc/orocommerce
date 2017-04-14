<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class EnvironmentTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\Environment()];
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
                    'The required option "environment" is missing.',
                ],
            ],
            'invalid_value' => [
                ['environment' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "environment" with value 12345 is invalid. Accepted values are: '.
                    '"https://apitest.authorize.net", "https://api2.authorize.net"',
                ],
            ],
            'valid_test_env' => [
                ['environment' => "https://apitest.authorize.net"],
                ['environment' => "https://apitest.authorize.net"],
            ],
            'valid_prod_env' => [
                ['environment' => "https://api2.authorize.net"],
                ['environment' => "https://api2.authorize.net"],
            ],
        ];
    }
}
