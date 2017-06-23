<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\PricingBundle\Api\Processor\RebuildPriceListsForWebsiteProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class RebuildPriceListsForWebsiteProcessorTest extends RebuildPriceListsTest
{
    /**
     * @var RebuildPriceListsForWebsiteProcessor
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RebuildPriceListsForWebsiteProcessor(
            $this->relationChangesHandler
        );
    }

    public function testProcessForNullResult()
    {
        $context = $this->createContext(null);

        $this->relationChangesHandler->expects(static::any())
            ->method('handleCustomerGroupChange');

        $this->processor->process($context);
    }

    public function testProcessForWebsiteFallback()
    {
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($website);

        $context = $this->createContext($fallback);

        $this->relationChangesHandler->expects(static::once())
            ->method('handleWebsiteChange')
            ->with($website);

        $this->processor->process($context);
    }

    public function testProcessForMultipleWebsiteFallbacks()
    {
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($website);

        $website2 = $this->createWebsite(4);
        $fallback2 = $this->createFallback($website2);

        $context = $this->createContext([$fallback, $fallback, $fallback2]);

        $this->relationChangesHandler->expects(static::exactly(2))
            ->method('handleWebsiteChange')
            ->withConsecutive(
                [$website],
                [$website2]
            );

        $this->processor->process($context);
    }

    public function testProcessForPriceListToWebsite()
    {
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToWebsite($website);

        $context = $this->createContext($relation);

        $this->relationChangesHandler->expects(static::once())
            ->method('handleWebsiteChange')
            ->with($website);

        $this->processor->process($context);
    }

    public function testProcessForMultiplePriceListToCustomerGroups()
    {
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToWebsite($website);

        $website2 = $this->createWebsite(4);
        $relation2 = $this->createPriceListToWebsite($website2);

        $context = $this->createContext([$relation, $relation, $relation2]);

        $this->relationChangesHandler->expects(static::exactly(2))
            ->method('handleWebsiteChange')
            ->withConsecutive(
                [$website],
                [$website2]
            );

        $this->processor->process($context);
    }

    /**
     * @param Website $website
     *
     * @return PriceListWebsiteFallback
     */
    private function createFallback(Website $website): PriceListWebsiteFallback
    {
        $fallback = new PriceListWebsiteFallback();
        $fallback->setWebsite($website)
            ->setFallback(0);

        return $fallback;
    }

    /**
     * @param Website $website
     *
     * @return PriceListToWebsite
     */
    private function createPriceListToWebsite(Website $website): PriceListToWebsite
    {
        $relation = new PriceListToWebsite();
        $relation->setWebsite($website);

        return $relation;
    }


}
