<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class ApruvePaymentMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruvePaymentMethod
     */
    protected $method;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->config = $this->createMock(ApruveConfigInterface::class);

        $this->method = new ApruvePaymentMethod($this->config);
    }

    public function testExecute()
    {
        // todo@webevt: make proper test once actions are processed properly.
    }

    public function testGetIdentifier()
    {
        $identifier = 'id';

        $this->config->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        $this->assertEquals($identifier, $this->method->getIdentifier());
    }

    /**
     * @param bool $expected
     * @param string $actionName
     *
     * @dataProvider supportsDataProvider
     */
    public function testSupports($expected, $actionName)
    {
        $this->assertEquals($expected, $this->method->supports($actionName));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [true, ApruvePaymentMethod::AUTHORIZE],
            [true, ApruvePaymentMethod::CAPTURE],
            [true, ApruvePaymentMethod::COMPLETE],
            [true, ApruvePaymentMethod::CANCEL],
        ];
    }

    public function testIsApplicable()
    {
        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertTrue($this->method->isApplicable($context));
    }
}
