<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\TransparentRedirect;

class TransparentRedirectTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new TransparentRedirect();
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
