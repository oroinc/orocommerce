<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Validator\Basic;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Basic\BasicShippingMethodValidator;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;

class BasicShippingMethodValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Common\CommonShippingMethodValidatorResultFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $commonShippingMethodValidatorResultFactory;

    /** @var BasicShippingMethodValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->commonShippingMethodValidatorResultFactory = $this->createMock(
            Common\CommonShippingMethodValidatorResultFactoryInterface::class
        );

        $this->validator = new BasicShippingMethodValidator($this->commonShippingMethodValidatorResultFactory);
    }

    public function testValidate()
    {
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $this->commonShippingMethodValidatorResultFactory->expects(self::once())
            ->method('createSuccessResult')
            ->willReturn($result);

        self::assertSame($result, $this->validator->validate($shippingMethod));
    }
}
