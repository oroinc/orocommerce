<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\CreateSecureToken;

class CreateSecureTokenTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new CreateSecureToken();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type casted to true value' => [['CREATESECURETOKEN' => true], ['CREATESECURETOKEN' => 'Y']],
            'invalid type casted to false value' => [['CREATESECURETOKEN' => 0], ['CREATESECURETOKEN' => 'N']],
            'valid' => [['CREATESECURETOKEN' => 'Y'], ['CREATESECURETOKEN' => 'Y']],
        ];
    }
}
