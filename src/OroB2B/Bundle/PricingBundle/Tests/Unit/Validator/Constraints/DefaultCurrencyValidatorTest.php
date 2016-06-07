<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\DefaultCurrency;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\DefaultCurrencyValidator;

class DefaultCurrencyValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultCurrency
     */
    protected $constraint;

    /**
     * @var DefaultCurrencyValidator
     */
    protected $validator;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->constraint = new DefaultCurrency();
        $this->validator = new DefaultCurrencyValidator($this->configManager);

        /** @var ExecutionContextInterface $context */
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset($this->configManager, $this->validator);
    }

    /**
     * @dataProvider validateValidDataProvider
     * @param string $value
     * @param array $availableCurrencies
     */
    public function testValidateValid($value, array $availableCurrencies)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_pricing.enabled_currencies')
            ->willReturn($availableCurrencies);

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateValidDataProvider()
    {
        return [
            [
                'value' => 'USD',
                'availableCurrencies' => ['USD', 'EUR', 'CAD']
            ]
        ];
    }

    /**
     * @dataProvider validateInvalidDataProvider
     * @param string $value
     * @param array $availableCurrencies
     */
    public function testValidateInvalid($value, array $availableCurrencies)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_pricing.enabled_currencies')
            ->willReturn($availableCurrencies);

        $this->context->expects($this->once())->method('addViolation');

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateInvalidDataProvider()
    {
        return [
            [
                'value' => 'EUR',
                'availableCurrencies' => ['USD', 'CAD']
            ]
        ];
    }
}
