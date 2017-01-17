<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config\Builder\Factory;

use Oro\Bundle\PayPalBundle\Method\Config\Builder\PayPalCreditCardConfigBuilder;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Method\Config\Builder\Factory\PayPalCreditCardConfigFactory;

class PayPalCreditCardConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $encoder;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var PayPalCreditCardConfigFactory
     */
    protected $payPalCreditCardConfigFactory;

    protected function setUp()
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->payPalCreditCardConfigFactory = new PayPalCreditCardConfigFactory(
            $this->encoder,
            $this->localizationHelper
        );
    }

    public function testCreatePayPalConfigBuilder()
    {
        $builder = $this->payPalCreditCardConfigFactory->createPayPalConfigBuilder();
        $expectedBuilder = new PayPalCreditCardConfigBuilder($this->encoder, $this->localizationHelper);
        static::assertEquals($expectedBuilder, $builder);
    }
}
