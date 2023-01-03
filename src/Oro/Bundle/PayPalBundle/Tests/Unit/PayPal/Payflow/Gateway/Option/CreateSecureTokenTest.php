<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\CreateSecureToken;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class CreateSecureTokenTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new CreateSecureToken()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type casted to true value' => [['CREATESECURETOKEN' => true], ['CREATESECURETOKEN' => 'Y']],
            'invalid type casted to false value' => [['CREATESECURETOKEN' => 0], ['CREATESECURETOKEN' => 'N']],
            'valid' => [['CREATESECURETOKEN' => 'Y'], ['CREATESECURETOKEN' => 'Y']],
        ];
    }
}
