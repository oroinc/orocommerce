<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Oro\Bundle\PricingBundle\Debug\Provider\PriceListsAssignmentProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\PriceListsAssignmentProviderInterface;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use PHPUnit\Framework\TestCase;

class PriceListsAssignmentProviderTest extends TestCase
{
    public function testGetPriceListAssignments()
    {
        $assignments1 = null;
        $assignments2 = [
            'section_title' => 'section_title',
            'link' => '/view-url',
            'link_title' => 'Test Name',
            'fallback' => null,
            'priceLists' => [],
            'stop' => false
        ];
        $assignments3 = [
            'section_title' => 'section_title',
            'link' => '/view-url',
            'link_title' => 'Test Name',
            'fallback' => null,
            'priceLists' => [new PriceListToWebsite()],
            'stop' => true
        ];
        $provider1 = $this->createMock(PriceListsAssignmentProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getPriceListAssignments')
            ->willReturn($assignments1);
        $provider2 = $this->createMock(PriceListsAssignmentProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getPriceListAssignments')
            ->willReturn($assignments2);
        $provider3 = $this->createMock(PriceListsAssignmentProviderInterface::class);
        $provider3->expects($this->once())
            ->method('getPriceListAssignments')
            ->willReturn($assignments3);
        $provider4 = $this->createMock(PriceListsAssignmentProviderInterface::class);
        $provider4->expects($this->never())
            ->method('getPriceListAssignments');

        $provider = new PriceListsAssignmentProvider([$provider1, $provider2, $provider3, $provider4]);

        $this->assertEquals([$assignments2, $assignments3], $provider->getPriceListAssignments());
    }
}
