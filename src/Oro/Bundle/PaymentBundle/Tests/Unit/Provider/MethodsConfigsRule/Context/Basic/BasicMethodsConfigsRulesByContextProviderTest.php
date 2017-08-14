<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider\MethodsConfigsRule\Context\Basic;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\Basic\BasicMethodsConfigsRulesByContextProvider;
use Oro\Bundle\PaymentBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class BasicMethodsConfigsRulesByContextProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodsConfigsRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var MethodsConfigsRulesFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filtrationService;

    /**
     * @var BasicMethodsConfigsRulesByContextProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->repository = $this->createMock(PaymentMethodsConfigsRuleRepository::class);

        $this->filtrationService = $this->createMock(MethodsConfigsRulesFiltrationServiceInterface::class);

        $this->provider = new BasicMethodsConfigsRulesByContextProvider(
            $this->filtrationService,
            $this->repository
        );
    }

    public function testGetAllFilteredPaymentMethodsConfigsWithPaymentAddress()
    {
        $currency = 'USD';
        $address = $this->createAddressMock();
        $website = $this->createWebsiteMock();
        $rulesFromDb = [$this->createPaymentMethodsConfigsRuleMock()];

        $this->repository->expects(static::once())
            ->method('getByDestinationAndCurrencyAndWebsite')
            ->with($address, $currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createContextMock();
        $context->method('getCurrency')
            ->willReturn($currency);
        $context->method('getBillingAddress')
            ->willReturn($address);
        $context->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createPaymentMethodsConfigsRuleMock()];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredPaymentMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        static::assertSame(
            $expectedRules,
            $this->provider->getPaymentMethodsConfigsRules($context)
        );
    }

    public function testGetAllFilteredPaymentMethodsConfigsWithoutPaymentAddress()
    {
        $currency = 'USD';
        $website = $this->createWebsiteMock();
        $rulesFromDb = [$this->createPaymentMethodsConfigsRuleMock()];

        $this->repository->expects(static::once())
            ->method('getByCurrencyAndWebsiteWithoutDestination')
            ->with($currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createContextMock();
        $context->method('getCurrency')
            ->willReturn($currency);
        $context->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createPaymentMethodsConfigsRuleMock()];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredPaymentMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        static::assertSame(
            $expectedRules,
            $this->provider->getPaymentMethodsConfigsRules($context)
        );
    }

    /**
     * @return PaymentMethodsConfigsRule|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPaymentMethodsConfigsRuleMock()
    {
        return $this->createMock(PaymentMethodsConfigsRule::class);
    }

    /**
     * @return PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createContextMock()
    {
        return $this->createMock(PaymentContextInterface::class);
    }

    /**
     * @return AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createAddressMock()
    {
        return $this->createMock(AddressInterface::class);
    }

    /**
     * @return Website|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createWebsiteMock()
    {
        return $this->createMock(Website::class);
    }
}
