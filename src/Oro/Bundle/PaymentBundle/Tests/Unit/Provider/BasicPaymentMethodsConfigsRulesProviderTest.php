<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\Converter\PaymentContextToRulesValueConverterInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Provider\BasicPaymentMethodsConfigsRulesProvider;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class BasicPaymentMethodsConfigsRulesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentContextToRulesValueConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContextToRulesValueConverterMock;

    /**
     * @var PaymentMethodsConfigsRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodsConfigsRuleRepositoryMock;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ruleFiltrationServiceMock;

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContextMock;

    public function setUp()
    {
        $this->paymentContextToRulesValueConverterMock = $this->getMock(
            PaymentContextToRulesValueConverterInterface::class
        );

        $this->paymentMethodsConfigsRuleRepositoryMock = $this
            ->getMockBuilder(PaymentMethodsConfigsRuleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ruleFiltrationServiceMock = $this->getMock(RuleFiltrationServiceInterface::class);

        $this->paymentContextMock = $this->getMock(PaymentContextInterface::class);
    }

    public function testGetFilteredPaymentMethodsConfigs()
    {
        $someAddress = $this->createAddressMock();
        $someCurrency = 'USD';
        $convertedContext = ['billingAddress' => $someAddress, 'currency' => $someCurrency];

        $ruleConfigs = [
            $this->createPaymentMethodsConfigsRuleMock(),
            $this->createPaymentMethodsConfigsRuleMock(),
            $this->createPaymentMethodsConfigsRuleMock(),
        ];

        $expectedConfigs = $ruleConfigs;
        array_pop($expectedConfigs);

        $this->paymentContextMock
            ->expects($this->exactly(2))
            ->method('getBillingAddress')
            ->willReturn($someAddress);

        $this->paymentContextMock
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($someCurrency);

        $this->paymentMethodsConfigsRuleRepositoryMock
            ->expects($this->once())
            ->method('getByDestinationAndCurrency')
            ->with($someAddress, $someCurrency)
            ->willReturn($ruleConfigs);

        $this->paymentContextToRulesValueConverterMock
            ->expects($this->once())
            ->method('convert')
            ->willReturn($convertedContext);

        $this->ruleFiltrationServiceMock
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($ruleConfigs, $convertedContext)
            ->willReturn($expectedConfigs);

        $provider = new BasicPaymentMethodsConfigsRulesProvider(
            $this->paymentContextToRulesValueConverterMock,
            $this->paymentMethodsConfigsRuleRepositoryMock,
            $this->ruleFiltrationServiceMock
        );

        $filteredConfigs = $provider->getFilteredPaymentMethodsConfigs($this->paymentContextMock);

        $this->assertEquals($expectedConfigs, $filteredConfigs);
    }

    public function testGetFilteredPaymentMethodsConfigsWithoutBillingAddress()
    {
        $someCurrency = 'USD';
        $convertedContext = ['currency' => $someCurrency];

        $ruleConfigs = [
            $this->createPaymentMethodsConfigsRuleMock(),
            $this->createPaymentMethodsConfigsRuleMock(),
            $this->createPaymentMethodsConfigsRuleMock(),
        ];

        $expectedConfigs = $ruleConfigs;
        array_pop($expectedConfigs);

        $this->paymentContextMock
            ->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn(null);

        $this->paymentContextMock
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($someCurrency);

        $this->paymentMethodsConfigsRuleRepositoryMock
            ->expects($this->never())
            ->method('getByDestinationAndCurrency');

        $this->paymentMethodsConfigsRuleRepositoryMock
            ->expects($this->once())
            ->method('getByCurrencyWithoutDestination')
            ->with($someCurrency)
            ->willReturn($ruleConfigs);

        $this->paymentContextToRulesValueConverterMock
            ->expects($this->once())
            ->method('convert')
            ->willReturn($convertedContext);

        $this->ruleFiltrationServiceMock
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($ruleConfigs, $convertedContext)
            ->willReturn($expectedConfigs);

        $provider = new BasicPaymentMethodsConfigsRulesProvider(
            $this->paymentContextToRulesValueConverterMock,
            $this->paymentMethodsConfigsRuleRepositoryMock,
            $this->ruleFiltrationServiceMock
        );

        $filteredConfigs = $provider->getFilteredPaymentMethodsConfigs($this->paymentContextMock);

        $this->assertEquals($expectedConfigs, $filteredConfigs);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AddressInterface
     */
    private function createAddressMock()
    {
        return $this->createMock(AddressInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AddressInterface
     */
    private function createPaymentMethodsConfigsRuleMock()
    {
        return $this->getMockBuilder(PaymentMethodsConfigsRule::class)->disableOriginalConstructor()->getMock();
    }
}
