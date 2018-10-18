<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Validator\Result\Error\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\ParameterBag\ParameterBagShippingMethodValidatorResultError;

class ParameterBagShippingMethodValidatorResultErrorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMessage()
    {
        $message = 'error message';
        $error = new ParameterBagShippingMethodValidatorResultError([
            'message' => $message,
        ]);
        static::assertEquals($message, $error->getMessage());
    }
}
