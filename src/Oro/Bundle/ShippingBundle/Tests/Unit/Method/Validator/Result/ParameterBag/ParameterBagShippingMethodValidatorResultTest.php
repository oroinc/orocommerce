<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Validator\Result\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Doctrine as DoctrineErrorCollection;
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
        $errors = [
            'errors' => new DoctrineErrorCollection\DoctrineShippingMethodValidatorResultErrorCollection(),
        ];
        $result = new ParameterBagShippingMethodValidatorResult($errors);

        static::assertSame($errors, $result->getErrors());
    }
}
