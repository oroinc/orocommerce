<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Resolver\ActiveCombinedPriceListResolver;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;

class ActiveCombinedPriceListResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActiveCombinedPriceListResolver */
    private $activeCombinedPriceListResolver;

    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var CombinedPriceListScheduleResolver */
    private $combinedPriceListScheduleResolver;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->combinedPriceListScheduleResolver = $this->createMock(CombinedPriceListScheduleResolver::class);

        $this->activeCombinedPriceListResolver = new ActiveCombinedPriceListResolver(
            $this->managerRegistry,
            $this->combinedPriceListScheduleResolver
        );
    }

    public function testGetActiveCplByFullCPLWithoutActivationRules(): void
    {
        $combinedPriceList = new CombinedPriceList();

        $activationRuleRepository = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $activationRuleRepository
            ->expects($this->once())
            ->method('hasActivationRules')
            ->with($combinedPriceList)
            ->willReturn(false);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($activationRuleRepository);

        $this->assertSame(
            $combinedPriceList,
            $this->activeCombinedPriceListResolver->getActiveCplByFullCPL($combinedPriceList)
        );
    }

    public function testGetActiveCplByFullCPLWithActivationRulesAndActiveCPL(): void
    {
        $combinedPriceList = new CombinedPriceList();
        $activeCombinedPriceList = new CombinedPriceList();

        $activationRuleRepository = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $activationRuleRepository
            ->expects($this->once())
            ->method('hasActivationRules')
            ->with($combinedPriceList)
            ->willReturn(true);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($activationRuleRepository);

        $this->combinedPriceListScheduleResolver
            ->expects($this->once())
            ->method('getActiveCplByFullCPL')
            ->willReturn($activeCombinedPriceList);

        $this->assertSame(
            $activeCombinedPriceList,
            $this->activeCombinedPriceListResolver->getActiveCplByFullCPL($combinedPriceList)
        );
    }

    public function testGetActiveCplByFullCPLWithActivationRulesAndWithoutActiveCPL(): void
    {
        $combinedPriceList = new CombinedPriceList();

        $activationRuleRepository = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $activationRuleRepository
            ->expects($this->once())
            ->method('hasActivationRules')
            ->with($combinedPriceList)
            ->willReturn(true);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($activationRuleRepository);

        $this->combinedPriceListScheduleResolver
            ->expects($this->once())
            ->method('getActiveCplByFullCPL')
            ->willReturn(null);

        $this->assertSame(
            $combinedPriceList,
            $this->activeCombinedPriceListResolver->getActiveCplByFullCPL($combinedPriceList)
        );
    }
}
