<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Handler;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\AffectedPriceListsHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AffectedPriceListsHandlerTest extends WebTestCase
{
    use MessageQueueTrait;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);

        $this->topic = Topics::CALCULATE_RULE;

        $this->cleanQueueMessageTraces();
    }

    public function testRecalculateByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        $affectedPriceListsHandler = $this->getContainer()->get('oro_pricing.handler.affected_price_lists_handler');
        $affectedPriceListsHandler->recalculateByPriceList(
            $priceList,
            AffectedPriceListsHandler::FIELD_ASSIGNED_PRODUCTS,
            false
        );

        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);

        $trace = $traces[0];
        $productId = $this->getProductIdFromTrace($trace);
        $this->assertEmpty($productId);

        /** @var PriceList $expectedPriceList */
        $expectedAffectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $this->assertEquals($expectedAffectedPriceList->getId(), $this->getPriceListIdFromTrace($trace));
    }
}
