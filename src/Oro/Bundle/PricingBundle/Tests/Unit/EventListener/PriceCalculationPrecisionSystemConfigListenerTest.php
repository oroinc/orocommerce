<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Command\PriceListScheduleRecalculateCommand;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleRepository;
use Oro\Bundle\PricingBundle\EventListener\PriceCalculationPrecisionSystemConfigListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceCalculationPrecisionSystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var RuleCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var PriceCalculationPrecisionSystemConfigListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(RuleCache::class);
        $this->session = $this->createMock(Session::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new PriceCalculationPrecisionSystemConfigListener(
            $this->registry,
            $this->cache,
            $this->requestStack,
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

        $event = new ConfigUpdateEvent([], 'global', 0);

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

        $event = new ConfigUpdateEvent(
            ['oro_pricing.price_calculation_precision' => ['old' => 1, 'new' => 2]],
            'global',
            0
        );

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
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(self::once())
            ->method('getSession')
            ->willReturn($this->session);
        $requestMock->expects($this->once())
            ->method('hasSession')
            ->willReturn(true);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);
        $repo = $this->createMock(PriceRuleRepository::class);
        $repo->expects($this->once())
            ->method('getRuleIds')
            ->willReturn([1]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceRule::class)
            ->willReturn($repo);

        $event = new ConfigUpdateEvent(
            ['oro_pricing.price_calculation_precision' => ['old' => 1, 'new' => 2]],
            'global',
            0
        );

        $this->listener->updateAfter($event);
    }
}
