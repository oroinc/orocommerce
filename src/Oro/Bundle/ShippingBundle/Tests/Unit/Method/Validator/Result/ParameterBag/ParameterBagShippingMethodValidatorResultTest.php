<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Validator\Result\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ParameterBag\ParameterBagShippingMethodValidatorResult;

class ParameterBagShippingMethodValidatorResultTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateCommonFactory()
    {
        $result = new ParameterBagShippingMethodValidatorResult();

        static::assertInstanceOf(
            Common\ParameterBag\ParameterBagCommonShippingMethodValidatorResultFactory::class,
            $result->createCommonFactory()
        );
    }

    public function testGetErrors()
    {
        $errors = new \ArrayObject();
        $result = new ParameterBagShippingMethodValidatorResult([
            'errors' => $errors,
        ]);
        static::assertSame($errors, $result->getErrors());
    }
}
