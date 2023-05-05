<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\MethodsConfigsRule\Context\RegardlessDestination;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\RegardlessDestination;
use Oro\Bundle\ShippingBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class RegardlessDestinationMethodsConfigsRulesByContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MethodsConfigsRulesFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var ShippingMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var RegardlessDestination\RegardlessDestinationMethodsConfigsRulesByContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(MethodsConfigsRulesFiltrationServiceInterface::class);
        $this->repository = $this->createMock(ShippingMethodsConfigsRuleRepository::class);
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ShippingMethodsConfigsRule::class)
            ->willReturn($this->repository);

        $this->provider = new RegardlessDestination\RegardlessDestinationMethodsConfigsRulesByContextProvider(
            $this->filtrationService,
            $doctrine,
            $this->organizationProvider
        );
    }

    public function testGetAllFilteredShippingMethodsConfigsWithShippingAddress()
    {
        $currency = 'USD';
        $address = $this->createMock(AddressInterface::class);
        $website = $this->createMock(Website::class);
        $organization = $this->createMock(Organization::class);
        $rulesFromDb = [$this->createMock(ShippingMethodsConfigsRule::class)];

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->repository->expects(self::once())
            ->method('getByDestinationAndCurrencyAndWebsite')
            ->with(
                self::identicalTo($address),
                $currency,
                self::identicalTo($website),
                self::identicalTo($organization)
            )
            ->willReturn($rulesFromDb);

        $this->repository->expects(self::never())
            ->method('getByCurrencyAndWebsite');

        $context = $this->createMock(ShippingContextInterface::class);
        $context->expects(self::any())
            ->method('getCurrency')
            ->willReturn($currency);
        $context->expects(self::any())
            ->method('getShippingAddress')
            ->willReturn($address);
        $context->expects(self::any())
            ->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [
            $this->createMock(ShippingMethodsConfigsRule::class),
            $this->createMock(ShippingMethodsConfigsRule::class),
        ];

        $this->filtrationService->expects(self::once())
            ->method('getFilteredShippingMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        self::assertSame(
            $expectedRules,
            $this->provider->getShippingMethodsConfigsRules($context)
        );
    }

    public function testGetAllFilteredShippingMethodsConfigsWithoutShippingAddress()
    {
        $currency = 'USD';
        $website = $this->createMock(Website::class);
        $organization = $this->createMock(Organization::class);
        $rulesFromDb = [$this->createMock(ShippingMethodsConfigsRule::class)];

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->repository->expects(self::once())
            ->method('getByCurrencyAndWebsite')
            ->with($currency, self::identicalTo($website), self::identicalTo($organization))
            ->willReturn($rulesFromDb);

        $context = $this->createMock(ShippingContextInterface::class);
        $context->expects(self::any())
            ->method('getCurrency')
            ->willReturn($currency);
        $context->expects(self::any())
            ->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createMock(ShippingMethodsConfigsRule::class)];

        $this->filtrationService->expects(self::once())
            ->method('getFilteredShippingMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        self::assertSame(
            $expectedRules,
            $this->provider->getShippingMethodsConfigsRules($context)
        );
    }
}
