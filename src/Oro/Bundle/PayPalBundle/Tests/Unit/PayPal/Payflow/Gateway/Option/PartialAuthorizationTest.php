<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\PartialAuthorization;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class PartialAuthorizationTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new PartialAuthorization()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type casted to true value' => [['PARTIALAUTH' => true], ['PARTIALAUTH' => 'Y']],
            'invalid type casted to false value' => [['PARTIALAUTH' => 0], ['PARTIALAUTH' => 'N']],
            'valid' => [['PARTIALAUTH' => 'Y'], ['PARTIALAUTH' => 'Y']],
        ];
    }
}
