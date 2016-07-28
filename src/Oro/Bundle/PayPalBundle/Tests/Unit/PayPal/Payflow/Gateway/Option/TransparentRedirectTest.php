<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\TransparentRedirect;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class TransparentRedirectTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new TransparentRedirect()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type casted to true value' => [['SILENTTRAN' => true], ['SILENTTRAN' => 'TRUE']],
            'invalid type casted to false value' => [['SILENTTRAN' => 0], ['SILENTTRAN' => 'FALSE']],
            'valid' => [['SILENTTRAN' => 'TRUE'], ['SILENTTRAN' => 'TRUE']],
        ];
    }
}
