<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider\MethodsConfigsRule\Context\RegardlessDestination;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\RegardlessDestination;
use Oro\Bundle\PaymentBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class RegardlessDestinationMethodsConfigsRulesByContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MethodsConfigsRulesFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var PaymentMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var RegardlessDestination\RegardlessDestinationMethodsConfigsRulesByContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(MethodsConfigsRulesFiltrationServiceInterface::class);
        $this->repository = $this->createMock(PaymentMethodsConfigsRuleRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(PaymentMethodsConfigsRule::class)
            ->willReturn($this->repository);

        $this->provider = new RegardlessDestination\RegardlessDestinationMethodsConfigsRulesByContextProvider(
            $this->filtrationService,
            $doctrine
        );
    }

    public function testGetAllFilteredPaymentMethodsConfigsWithPaymentAddress()
    {
        $currency = 'USD';
        $address = $this->createMock(AddressInterface::class);
        $website = $this->createMock(Website::class);
        $rulesFromDb = [
            $this->createMock(PaymentMethodsConfigsRule::class),
            $this->createMock(PaymentMethodsConfigsRule::class),
        ];

        $this->repository->expects(self::once())
            ->method('getByDestinationAndCurrencyAndWebsite')
            ->with($address, $currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::any())
            ->method('getCurrency')
            ->willReturn($currency);
        $context->expects(self::any())
            ->method('getBillingAddress')
            ->willReturn($address);
        $context->expects(self::any())
            ->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createMock(PaymentMethodsConfigsRule::class)];

        $this->filtrationService->expects(self::once())
            ->method('getFilteredPaymentMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        self::assertSame(
            $expectedRules,
            $this->provider->getPaymentMethodsConfigsRules($context)
        );
    }

    public function testGetAllFilteredPaymentMethodsConfigsWithoutPaymentAddress()
    {
        $currency = 'USD';
        $website = $this->createMock(Website::class);
        $rulesFromDb = [$this->createMock(PaymentMethodsConfigsRule::class)];

        $this->repository->expects(self::once())
            ->method('getByCurrencyAndWebsite')
            ->with($currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::any())
            ->method('getCurrency')
            ->willReturn($currency);
        $context->expects(self::any())
            ->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createMock(PaymentMethodsConfigsRule::class)];

        $this->filtrationService->expects(self::once())
            ->method('getFilteredPaymentMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        self::assertSame(
            $expectedRules,
            $this->provider->getPaymentMethodsConfigsRules($context)
        );
    }
}
