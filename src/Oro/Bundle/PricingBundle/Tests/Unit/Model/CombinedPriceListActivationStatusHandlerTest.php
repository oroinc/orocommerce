<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListActivationStatusHandler;

class CombinedPriceListActivationStatusHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var CombinedPriceListActivationStatusHandler
     */
    private $helper;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->helper = new CombinedPriceListActivationStatusHandler(
            $this->registry
        );
    }

    public function testIsReadyForBuildNoSchedules()
    {
        $cpl = new CombinedPriceList();

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('hasActivationRules')
            ->with($cpl)
            ->willReturn(false);

        $this->assertTrue($this->helper->isReadyForBuild($cpl));
    }

    public function testIsReadyForBuildHasActiveScedule()
    {
        $cpl = new CombinedPriceList();

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('hasActivationRules')
            ->with($cpl)
            ->willReturn(true);
        $repo->expects($this->once())
            ->method('hasActiveRuleByScheduledCpl')
            ->with($cpl, $this->isInstanceOf(\DateTime::class))
            ->willReturn(true);

        $this->assertTrue($this->helper->isReadyForBuild($cpl));
    }

    public function testIsReadyForBuildHasNoActiveScedule()
    {
        $cpl = new CombinedPriceList();

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('hasActivationRules')
            ->with($cpl)
            ->willReturn(true);
        $repo->expects($this->once())
            ->method('hasActiveRuleByScheduledCpl')
            ->with($cpl, $this->isInstanceOf(\DateTime::class))
            ->willReturn(false);

        $this->assertFalse($this->helper->isReadyForBuild($cpl));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function assertRepositoryCall()
    {
        $repo = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListActivationRule::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceListActivationRule::class)
            ->willReturn($em);

        return $repo;
    }
}
