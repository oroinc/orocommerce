<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\EventListener\UpdateFlatPriceListSystemConfigListener;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandlerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;

class UpdateFlatPriceListSystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PriceListRelationTriggerHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var UpdateFlatPriceListSystemConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->triggerHandler = $this->createMock(PriceListRelationTriggerHandlerInterface::class);

        $this->listener = new UpdateFlatPriceListSystemConfigListener(
            $this->doctrine,
            $this->triggerHandler
        );
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }

    public function testOnUpdateAfterWebsiteScope(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with('oro_pricing.default_price_list')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getScope')
            ->willReturn('website');
        $event->expects(self::atLeastOnce())
            ->method('getScopeId')
            ->willReturn(1);

        $website = $this->getWebsite(1);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, 1)
            ->willReturn($website);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $this->triggerHandler->expects(self::once())
            ->method('handleWebsiteChange')
            ->with($website);

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterAppScope(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with('oro_pricing.default_price_list')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getScope')
            ->willReturn('app');
        $event->expects(self::never())
            ->method('getScopeId');

        $this->triggerHandler->expects(self::once())
            ->method('handleConfigChange');

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterNoChanges(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with('oro_pricing.default_price_list')
            ->willReturn(false);

        $this->triggerHandler->expects(self::never())
            ->method(self::anything());

        $this->listener->onUpdateAfter($event);
    }
}
