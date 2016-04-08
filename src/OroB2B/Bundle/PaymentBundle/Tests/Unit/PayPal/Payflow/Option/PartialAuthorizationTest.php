<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\PartialAuthorization;

class PartialAuthorizationTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new PartialAuthorization();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type casted to true value' => [['PARTIALAUTH' => true], ['PARTIALAUTH' => 'Y']],
            'invalid type casted to false value' => [['PARTIALAUTH' => 0], ['PARTIALAUTH' => 'N']],
            'valid' => [['PARTIALAUTH' => 'Y'], ['PARTIALAUTH' => 'Y']],
        ];
    }
}
