<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ORM\Walker;

use Doctrine\ORM\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceShardWalkerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testWalkSelectStatementLevels0And2()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $dql = <<<DQL
SELECT t1.id product, 4 priceList, 'each' unit, 'USD' currency, 1 quantity,
 t1.sku productSku, 1 priceRule, _plt2.value + 1 value 
FROM Oro\Bundle\ProductBundle\Entity\Product t1 
LEFT JOIN Oro\Bundle\PricingBundle\Entity\PriceListToProduct _plt0 
    WITH _plt0.priceList = :priceList1 AND _plt0.product = t1 
LEFT JOIN Oro\Bundle\PricingBundle\Entity\PriceList _plt1 
    WITH _plt0.priceList = _plt1 
LEFT JOIN Oro\Bundle\PricingBundle\Entity\ProductPrice _plt2 
    WITH _plt2.product = t1 AND _plt2.priceList = _plt1 
INNER JOIN t1.unitPrecisions _allowedUnit 
INNER JOIN Oro\Bundle\PricingBundle\Entity\PriceListToProduct priceListToProduct 
    WITH priceListToProduct.product = t1 
WHERE _plt2.value + 1 >= 0 AND ((_plt2.currency = :_vn0 AND _plt2.quantity = 1) AND _plt2.unit = :_vn1) 
    AND _allowedUnit.unit = :requiredUnitUnit AND priceListToProduct.priceList = :priceList 
    AND (NOT(EXISTS(
        SELECT productPriceManual FROM Oro\Bundle\PricingBundle\Entity\ProductPrice productPriceManual 
        WHERE productPriceManual.product = t1 AND productPriceManual.priceList = :priceListManual 
        AND productPriceManual.unit = 'each' AND productPriceManual.currency = 'USD' 
        AND productPriceManual.quantity = 1)
    ))
DQL;
        /** @var Query $query */
        $query = $em->createQuery($dql);
        $query->setParameters([
            'priceList1' => 1,
            '_vn0' => 'USD',
            '_vn1' => 'item',
            'requiredUnitUnit' => 'item',
            'priceList' => 2,
            'priceListManual' => 2
        ]);
        $this->getContainer()->get('oro_pricing.shard_manager')->setEnableSharding(true);
        $this->getContainer()->get('oro_entity.query_hint_resolver')->resolveHints($query, ['HINT_PRICE_SHARD']);
        $sql = $query->getSQL();
        static::assertStringContainsString(' oro_price_product_1 ', $sql);
        static::assertStringContainsString(' oro_price_product_2 ', $sql);
        static::assertStringNotContainsString(' oro_price_product ', $sql);
    }

    public function testWalkSelectStatementLevel1()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $dql = <<<DQL
SELECT _plt1, _plt2
FROM Oro\Bundle\PricingBundle\Entity\PriceList _plt1 
LEFT JOIN _plt1.prices _plt2 
    WITH _plt2.priceList = _plt1 
WHERE _plt1 = :priceList
DQL;
        /** @var Query $query */
        $query = $em->createQuery($dql);
        $query->setParameters([
            'priceList' => 1,
        ]);

        $this->getContainer()->get('oro_entity.query_hint_resolver')->resolveHints($query, ['HINT_PRICE_SHARD']);
        $sql = $query->getSQL();
        static::assertStringContainsString(' oro_price_product_1 ', $sql);
        static::assertStringNotContainsString(' oro_price_product ', $sql);
    }
}
