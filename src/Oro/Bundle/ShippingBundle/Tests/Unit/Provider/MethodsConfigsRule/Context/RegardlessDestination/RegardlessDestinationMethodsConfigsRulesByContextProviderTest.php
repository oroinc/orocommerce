<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\MethodsConfigsRule\Context\RegardlessDestination;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\RegardlessDestination;
use Oro\Bundle\ShippingBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class RegardlessDestinationMethodsConfigsRulesByContextProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodsConfigsRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var MethodsConfigsRulesFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filtrationService;

    /**
     * @var RegardlessDestination\RegardlessDestinationMethodsConfigsRulesByContextProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->repository = $this->createMock(ShippingMethodsConfigsRuleRepository::class);

        $this->filtrationService = $this->createMock(MethodsConfigsRulesFiltrationServiceInterface::class);

        $this->provider = new RegardlessDestination\RegardlessDestinationMethodsConfigsRulesByContextProvider(
            $this->filtrationService,
            $this->repository
        );
    }

    public function testGetAllFilteredShippingMethodsConfigsWithShippingAddress()
    {
        $currency = 'USD';
        $address = $this->createAddressMock();
        $website = $this->createWebsiteMock();
        $rulesFromDb = [$this->createShippingMethodsConfigsRuleMock()];

        $this->repository->expects(static::once())
            ->method('getByDestinationAndCurrencyAndWebsite')
            ->with($address, $currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createContextMock();
        $context->method('getCurrency')
            ->willReturn($currency);
        $context->method('getShippingAddress')
            ->willReturn($address);
        $context->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createShippingMethodsConfigsRuleMock()];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredShippingMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        static::assertSame(
            $expectedRules,
            $this->provider->getShippingMethodsConfigsRules($context)
        );
    }

    public function testGetAllFilteredShippingMethodsConfigsWithoutShippingAddress()
    {
        $currency = 'USD';
        $website = $this->createWebsiteMock();
        $rulesFromDb = [$this->createShippingMethodsConfigsRuleMock()];

        $this->repository->expects(static::once())
            ->method('getByCurrencyAndWebsite')
            ->with($currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createContextMock();
        $context->method('getCurrency')
            ->willReturn($currency);
        $context->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createShippingMethodsConfigsRuleMock()];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredShippingMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        static::assertSame(
            $expectedRules,
            $this->provider->getShippingMethodsConfigsRules($context)
        );
    }

    /**
     * @return ShippingMethodsConfigsRule|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createShippingMethodsConfigsRuleMock()
    {
        return $this->createMock(ShippingMethodsConfigsRule::class);
    }

    /**
     * @return ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createContextMock()
    {
        return $this->createMock(ShippingContextInterface::class);
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
