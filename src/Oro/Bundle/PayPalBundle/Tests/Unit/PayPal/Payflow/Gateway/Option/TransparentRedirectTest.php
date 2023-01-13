<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\TransparentRedirect;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class TransparentRedirectTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new TransparentRedirect()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type casted to true value' => [['SILENTTRAN' => true], ['SILENTTRAN' => 'TRUE']],
            'invalid type casted to false value' => [['SILENTTRAN' => 0], ['SILENTTRAN' => 'FALSE']],
            'valid' => [['SILENTTRAN' => 'TRUE'], ['SILENTTRAN' => 'TRUE']],
        ];
    }
}
