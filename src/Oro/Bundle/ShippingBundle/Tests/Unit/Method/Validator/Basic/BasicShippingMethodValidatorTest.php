<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Validator\Basic;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Basic\BasicShippingMethodValidator;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;

class BasicShippingMethodValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Common\CommonShippingMethodValidatorResultFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commonShippingMethodValidatorResultFactory;

    /**
     * @var BasicShippingMethodValidator
     */
    private $validator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->commonShippingMethodValidatorResultFactory = $this->createMock(
            Common\CommonShippingMethodValidatorResultFactoryInterface::class
        );

        $this->validator = new BasicShippingMethodValidator($this->commonShippingMethodValidatorResultFactory);
    }

    public function testValidate()
    {
        /** @var ShippingMethodInterface $shippingMethod */
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $this->commonShippingMethodValidatorResultFactory->expects(static::once())
            ->method('createSuccessResult')
            ->willReturn($result);

        static::assertSame($result, $this->validator->validate($shippingMethod));
    }
}
