<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Validator\Result\Factory\Common\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ParameterBag\ParameterBagShippingMethodValidatorResult;

class ParameterBagCommonShippingMethodValidatorResultFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Factory\Common\ParameterBag\ParameterBagCommonShippingMethodValidatorResultFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->factory = new Factory\Common\ParameterBag\ParameterBagCommonShippingMethodValidatorResultFactory();
    }

    public function testCreateSuccessResult()
    {
        static::assertEquals(new ParameterBagShippingMethodValidatorResult(
            [
                'errors' => new Error\Collection\Doctrine\DoctrineShippingMethodValidatorResultErrorCollection(),
            ]
        ), $this->factory->createSuccessResult());
    }

    public function testCreateErrorResult()
    {
        /** @var Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $errors */
        $errors = $this->createMock(Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface::class);
        static::assertEquals(new ParameterBagShippingMethodValidatorResult(
            [
                'errors' => $errors,
            ]
        ), $this->factory->createErrorResult($errors));
    }
}
