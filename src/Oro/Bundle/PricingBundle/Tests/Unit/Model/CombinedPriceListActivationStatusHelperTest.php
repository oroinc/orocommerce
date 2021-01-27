<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListActivationStatusHelper;

class CombinedPriceListActivationStatusHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var CombinedPriceListActivationStatusHelper
     */
    private $helper;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->helper = new CombinedPriceListActivationStatusHelper(
            $this->registry,
            $this->configManager
        );
    }

    public function testIsReadyForBuildNoSchedules()
    {
        $cpl = new CombinedPriceList();

        $this->configManager->expects($this->never())
            ->method($this->anything());

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

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.offset_of_processing_cpl_prices')
            ->willReturn(10);

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('hasActivationRules')
            ->with($cpl)
            ->willReturn(true);
        $rule = new CombinedPriceListActivationRule();
        $repo->expects($this->once())
            ->method('getActiveRuleByScheduledCpl')
            ->with($cpl, $this->isInstanceOf(\DateTime::class))
            ->willReturn($rule);

        $this->assertTrue($this->helper->isReadyForBuild($cpl));
    }

    public function testIsReadyForBuildHasNoActiveScedule()
    {
        $cpl = new CombinedPriceList();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.offset_of_processing_cpl_prices')
            ->willReturn(10);

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('hasActivationRules')
            ->with($cpl)
            ->willReturn(true);
        $repo->expects($this->once())
            ->method('getActiveRuleByScheduledCpl')
            ->with($cpl, $this->isInstanceOf(\DateTime::class))
            ->willReturn(null);

        $this->assertFalse($this->helper->isReadyForBuild($cpl));
    }

    public function testGetActivateDate()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.offset_of_processing_cpl_prices')
            ->willReturn(10);

        $date = $this->helper->getActivateDate();

        $activateDateMore = new \DateTime('now', new \DateTimeZone('UTC'));
        $activateDateMore->add(new \DateInterval(sprintf('PT%dM', 10 * 60)));

        $activateDateLess = new \DateTime('now', new \DateTimeZone('UTC'));
        $activateDateLess->add(new \DateInterval(sprintf('PT%dM', 10 * 60 - 5)));

        // Check active date is within time interval now + 10h - 5s and now + 10h
        $this->assertLessThanOrEqual($activateDateMore, $date);
        $this->assertGreaterThanOrEqual($activateDateLess, $date);
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
