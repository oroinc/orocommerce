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

    #[\Override]
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
        $event = new ConfigUpdateEvent(['oro_pricing.default_price_list' => ['old' => 1, 'new' => 2]], 'website', 1);

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
        $event = new ConfigUpdateEvent(['oro_pricing.default_price_list' => ['old' => 1, 'new' => 2]], 'global', 0);

        $this->triggerHandler->expects(self::once())
            ->method('handleConfigChange');

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterNoChanges(): void
    {
        $event = new ConfigUpdateEvent([], 'website', 1);

        $this->triggerHandler->expects(self::never())
            ->method(self::anything());

        $this->listener->onUpdateAfter($event);
    }
}
