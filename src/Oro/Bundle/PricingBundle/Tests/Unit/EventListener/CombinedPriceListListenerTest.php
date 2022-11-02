<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;
use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListListener;

class CombinedPriceListListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CombinedPriceListActivationPlanBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $activationPlanBuilder;

    /** @var CombinedPriceListListener */
    private $listener;

    protected function setUp(): void
    {
        $this->activationPlanBuilder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);

        $this->listener = new CombinedPriceListListener($this->activationPlanBuilder);
    }

    public function testOnCreate()
    {
        $combinedPriceList = new CombinedPriceList();

        $this->activationPlanBuilder->expects($this->once())
            ->method('buildByCombinedPriceList')
            ->with($combinedPriceList);

        $this->listener->onCreate(new CombinedPriceListCreateEvent($combinedPriceList));
    }

    public function testOnCreateSkibActivationPlanBuild()
    {
        $combinedPriceList = new CombinedPriceList();

        $this->activationPlanBuilder->expects($this->never())
            ->method('buildByCombinedPriceList');

        $event = new CombinedPriceListCreateEvent(
            $combinedPriceList,
            [CombinedPriceListActivationPlanBuilder::SKIP_ACTIVATION_PLAN_BUILD => true]
        );
        $this->listener->onCreate($event);
    }
}
