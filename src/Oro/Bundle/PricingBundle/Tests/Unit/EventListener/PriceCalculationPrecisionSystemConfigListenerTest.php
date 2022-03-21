<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Command\PriceListScheduleRecalculateCommand;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleRepository;
use Oro\Bundle\PricingBundle\EventListener\PriceCalculationPrecisionSystemConfigListener;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceCalculationPrecisionSystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var RuleCache|MockObject
     */
    private $cache;

    /**
     * @var Session|MockObject
     */
    private $session;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translator;

    private PriceCalculationPrecisionSystemConfigListener $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(RuleCache::class);
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new PriceCalculationPrecisionSystemConfigListener(
            $this->registry,
            $this->cache,
            $this->session,
            $this->translator
        );
    }

    public function testUpdateAfterNoChanges()
    {
        $this->cache->expects($this->never())
            ->method($this->anything());
        $this->session->expects($this->never())
            ->method($this->anything());
        $this->registry->expects($this->never())
            ->method($this->anything());

        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_pricing.price_calculation_precision')
            ->willReturn(false);

        $this->listener->updateAfter($event);
    }

    public function testUpdateAfterNoRules()
    {
        $this->cache->expects($this->never())
            ->method($this->anything());
        $this->session->expects($this->never())
            ->method($this->anything());

        $repo = $this->createMock(PriceRuleRepository::class);
        $repo->expects($this->once())
            ->method('getRuleIds')
            ->willReturn([]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceRule::class)
            ->willReturn($repo);

        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_pricing.price_calculation_precision')
            ->willReturn(true);

        $this->listener->updateAfter($event);
    }

    public function testUpdateAfter()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.pricing.system_configuration.fields.price_calculation_precision.notice')
            ->willReturn('NOTICE:');

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('pr_1');

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with(
                'warning',
                sprintf(
                    'NOTICE: <code>php bin/console %s --all</code>',
                    PriceListScheduleRecalculateCommand::getDefaultName()
                )
            );
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $repo = $this->createMock(PriceRuleRepository::class);
        $repo->expects($this->once())
            ->method('getRuleIds')
            ->willReturn([1]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceRule::class)
            ->willReturn($repo);

        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_pricing.price_calculation_precision')
            ->willReturn(true);

        $this->listener->updateAfter($event);
    }
}
