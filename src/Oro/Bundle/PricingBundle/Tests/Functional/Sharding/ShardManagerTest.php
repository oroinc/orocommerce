<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Sharding;

use Doctrine\DBAL\Connection;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;

class ShardManagerTest extends WebTestCase
{
    private ShardManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->manager = $this->getContainer()->get('oro_pricing.shard_manager');
        $this->manager->setEnableSharding(true);
    }

    public function testGetShardNameWithId()
    {
        $attributes = ['priceList' => 1];
        $actual = $this->manager->getShardName(ProductPrice::class, $attributes);
        $this->assertSame('oro_price_product_1', $actual);
    }

    public function testGetShardNameWithObject()
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, 1);
        $attributes = ['priceList' => $priceList];
        $actual = $this->manager->getShardName(ProductPrice::class, $attributes);
        $this->assertSame('oro_price_product_1', $actual);
    }

    public function testGetShardNameExceptionWhenParamMissing()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Required attribute \'priceList\' for generation of shard name missing.');

        $this->manager->getEnabledShardName(ProductPrice::class, []);
    }

    public function testGetShardNameExceptionWhenParamNotValid()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wrong type of \'priceList\' to generate shard name.');

        $this->manager->getEnabledShardName(ProductPrice::class, ['priceList' => new \stdClass()]);
    }

    public function testCreateAndDeleteNewShard()
    {
        $shardName = 'oro_price_product_0';

        $this->assertFalse($this->manager->exists($shardName));

        $this->manager->create(ProductPrice::class, $shardName);
        $this->assertTrue($this->manager->exists($shardName));

        $this->manager->delete($shardName);
        $this->assertFalse($this->manager->exists($shardName));
    }

    public function testSerialization()
    {
        $this->manager->addEntityForShard('price', ProductPrice::class);
        $result = serialize($this->manager);
        /** @var ShardManager $newManager */
        $newManager = unserialize($result);
        $newManager->setRegistry($this->getContainer()->get('doctrine'));
        $this->assertEquals($this->manager->getShardMap(), $newManager->getShardMap());
    }

    public function testGetDiscriminationField()
    {
        $discriminationFieldName = 'priceList';
        $this->assertEquals($this->manager->getDiscriminationField(ProductPrice::class), $discriminationFieldName);
    }

    public function testGetDiscriminationColumn()
    {
        $discriminationColumnName = 'price_list_id';
        $this->assertEquals($this->manager->getDiscriminationColumn(ProductPrice::class), $discriminationColumnName);
    }

    public function testMoveData()
    {
        $pl1 = $this->createPriceList('PL1');
        $pl2 = $this->createPriceList('PL2');
        $prodSku = 'PROD1';
        $product = $this->createProduct($prodSku);

        $pricePl1 = $this->createPrice('91907077-73f9-444a-96f5-fb0a66167cc5', $pl1, $product, $prodSku);
        $pricePl2 = $this->createPrice('27cdc013-1340-46f4-ba80-8ea623772bfe', $pl2, $product, $prodSku);

        $baseTableName = 'oro_price_product';
        $shardName1 = 'oro_price_product_'.$pl1;
        $shardName2 = 'oro_price_product_'.$pl2;

        try {
            // move data from base table
            $this->manager->moveDataFromBaseTableToShard(ProductPrice::class);
            $this->assertPriceInTable($shardName1, $pricePl1);
            $this->assertPriceInTable($shardName2, $pricePl2);

            // move data from shards
            $this->manager->moveDataFromShardsToBaseTable(ProductPrice::class);
            $this->assertPriceInTable($baseTableName, $pricePl1);
            $this->assertPriceInTable($baseTableName, $pricePl2);
            $this->assertFalse($this->manager->exists($shardName1));
            $this->assertFalse($this->manager->exists($shardName2));
        } finally {
            $this->removeRow('oro_price_list', $pl1);
            $this->removeRow('oro_price_list', $pl2);
            $this->removeRow('oro_product', $product);
            $this->removeRow($baseTableName, $pricePl1);
            $this->removeRow($baseTableName, $pricePl1);
        }
    }

    private function createPriceList(string $name): int
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $connection->insert(
            'oro_price_list',
            [
                'name' => $name,
                'contain_schedule' => 1,
                'created_at' => '2017-03-29',
                'updated_at' => '2017-03-29',
            ]
        );

        return $connection->lastInsertId();
    }

    private function createProduct(string $sku): int
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $productName = 'product 1';
        $connection->insert(
            'oro_product',
            [
                'sku' => $sku,
                'created_at' => '2017-03-29',
                'updated_at' => '2017-03-29',
                'status' => 'enabled',
                'type' => 'simple',
                'name' => $productName,
                'name_uppercase' => mb_strtoupper($productName)
            ]
        );

        return $connection->lastInsertId();
    }

    private function createPrice(string $uid, int $pl, int $prodId, string $prodSku): string
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $connection->insert(
            'oro_price_product',
            [
                'id' => $uid,
                'unit_code' => 'item',
                'product_id' => $prodId,
                'product_sku' => $prodSku,
                'price_list_id' => $pl,
                'quantity' => 1,
                'value' => 100,
                'currency' => 'USD',
            ]
        );

        return $uid;
    }

    private function assertPriceInTable(string $table, string $id): void
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $exists = (boolean)$connection->executeQuery(
            'SELECT exists(SELECT 1 FROM ' . $table . ' WHERE id = :id)',
            ['id' => $id]
        )->fetchColumn(0);

        $this->assertTrue($exists, sprintf('Failed assert that %s in %s', $id, $table));
    }

    private function removeRow(string $table, string $id): void
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $connection->delete($table, ['id' => $id]);
    }

    public function testIsShardingEnabled()
    {
        $this->manager->setEnableSharding(false);
        $this->assertFalse($this->manager->isShardingEnabled());

        $this->manager->setEnableSharding(true);
        $this->assertTrue($this->manager->isShardingEnabled());
    }
}
