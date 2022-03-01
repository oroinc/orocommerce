<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList\Assignment;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class GetAssociatedWebsitesEventTest extends TestCase
{
    use EntityTrait;

    public function testBaseMethods()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $website1 = $this->getEntity(Website::class, ['id' => 10]);
        $website2 = $this->getEntity(Website::class, ['id' => 20]);
        $associations = ['config' => true];

        $event = new GetAssociatedWebsitesEvent($cpl, $associations);
        $event->addWebsiteAssociation($website1);
        $event->addWebsiteAssociation($website1);
        $event->addWebsiteAssociation($website2);

        $this->assertSame($cpl, $event->getCombinedPriceList());
        $this->assertSame($associations, $event->getAssociations());
        $this->assertEquals([10 => $website1, 20 => $website2], $event->getWebsites());
    }
}
