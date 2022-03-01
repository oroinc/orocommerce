<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList\Assignment;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use PHPUnit\Framework\TestCase;

class CollectByConfigEventTest extends TestCase
{
    /**
     * @dataProvider constructorArgumentsDataProvider
     */
    public function testBaseMethods(bool $includeSelfFallback, bool $collectOnCurrentLevel)
    {
        $event = new CollectByConfigEvent($includeSelfFallback, $collectOnCurrentLevel);
        $this->assertEquals($includeSelfFallback, $event->isIncludeSelfFallback());
        $this->assertEquals($collectOnCurrentLevel, $event->isCollectOnCurrentLevel());
    }

    public function constructorArgumentsDataProvider(): \Generator
    {
        yield [false, false];
        yield [true, false];
        yield [false, true];
        yield [true, true];
    }

    public function testAddAssociation()
    {
        $event = new CollectByConfigEvent();
        $event->addAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            ['config' => true]
        );
        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => ['config' => true]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }

    public function testMergeAssociation()
    {
        $event = new CollectByConfigEvent();
        $event->addAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            ['config' => true]
        );

        $event2 = new CollectByConfigEvent();
        $event2->addAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            ['config' => true]
        );
        $event->mergeAssociations($event2);

        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => ['config' => true]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }
}
