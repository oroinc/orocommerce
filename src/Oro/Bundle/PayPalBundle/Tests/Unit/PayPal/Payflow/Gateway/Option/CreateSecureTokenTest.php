<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\CreateSecureToken;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class CreateSecureTokenTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new CreateSecureToken()];
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
