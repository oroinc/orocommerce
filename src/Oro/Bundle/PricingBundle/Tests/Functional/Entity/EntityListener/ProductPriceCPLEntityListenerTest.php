<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\Sharding\ProductPriceReference;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPriceCPLEntityListenerTest extends WebTestCase
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductPrices::class,
        ]);

        $this->getContainer()->get('oro_pricing.price_list_trigger_handler')->sendScheduledTriggers();
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        $this->registry = $this->getContainer()->get('doctrine');
    }

    /**
     * @var ShardManager
     */
    protected $shardManager;

    public function testOnCreate()
    {
        /** @var EntityManagerInterface $priceManager */
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');

        // create two prices with same product and priceList
        // to ensure that duplicate triggers won't be flushed
        $priceManager->persist(
            $this->getProductPrice(
                LoadProductData::PRODUCT_5,
                LoadPriceLists::PRICE_LIST_1,
                Price::create(10, 'USD'),
                10
            )
        );
        $priceManager->persist(
            $this->getProductPrice(
                LoadProductData::PRODUCT_5,
                LoadPriceLists::PRICE_LIST_1,
                Price::create(100, 'USD'),
                100
            )
        );
        $priceManager->flush();

        // assert that needed productPriceRelationCreated
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductPrice::class);
        $plToProductRelations = $em->getRepository('OroPricingBundle:PriceListToProduct')->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_5),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
        ]);
        $this->assertCount(1, $plToProductRelations);
    }

    public function testOnUpdateChangeTriggerCreated()
    {
        $handler = $this->getContainer()->get('oro_pricing.price_list_trigger_handler');
        /** @var PriceManager $priceManager */
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        /** @var ProductPrice $productPrice */

        $priceList = $this->getReference('price_list_2');
        $productPrices = $this->registry->getRepository(ProductPrice::class)
            ->findByPriceList(
                $this->shardManager,
                $priceList,
                [
                    'priceList' => $priceList,
                    'product' => $this->getReference(LoadProductData::PRODUCT_2)
                ]
            );
        $productPrice = $productPrices[0];
        $productPrice->setPrice(Price::create(1000, 'EUR'));
        $priceManager->persist($productPrice);
        $priceManager->flush();
        $this->assertAttributeCount(1, 'scheduledTriggers', $handler);
    }

    public function testOnUpdatePriceToProductRelation()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();
        $priceRepository = $em->getRepository(ProductPrice::class);
        /** @var PriceListToProductRepository $repository */
        $repository = $em->getRepository(PriceListToProduct::class);
        $repository
            ->createQueryBuilder('pltp')
            ->delete()
            ->getQuery()
            ->execute();

        /** @var ProductPrice $productPrice1 */
        $productPrices = $priceRepository->findByPriceList(
            $this->shardManager,
            $this->getReference(LoadPriceLists::PRICE_LIST_1),
            [
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
                'product' => $this->getReference(LoadProductData::PRODUCT_1)
            ]
        );
        $productPrice1 = $productPrices[0];

        $productPrices = $priceRepository->findByPriceList(
            $this->shardManager,
            $this->getReference(LoadPriceLists::PRICE_LIST_2),
            [
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_2),
                'product' => $this->getReference(LoadProductData::PRODUCT_2)
            ]
        );
        $productPrice2 = $productPrices[0];

        $productPrices = $priceRepository->findByPriceList(
            $this->shardManager,
            $this->getReference(LoadPriceLists::PRICE_LIST_2),
            [
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_2),
                'product' => $this->getReference(LoadProductData::PRODUCT_1)
            ]
        );
        $productPrice3 = $productPrices[0];

        /** @var Product $newProduct */
        $newProduct = $this->getReference(LoadProductData::PRODUCT_2);
        $productPrice1->setProduct($newProduct);

        /** @var PriceList $newPriceList */
        $newPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_5);
        $productPrice2->setPriceList($newPriceList);

        $productPrice3->setQuantity(10000);
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->persist($productPrice1);
        $priceManager->persist($productPrice2);
        $priceManager->persist($productPrice3);
        $priceManager->flush();

        // new relation should be created when new product specified
        $this->assertCount(1, $repository->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_2),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
        ]));

        // new relation should be created when new price list specified
        $this->assertCount(1, $repository->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_2),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_5),
        ]));

        $this->assertCount(3, $repository->findBy([]));
    }

    public function testOnDelete()
    {
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_1));
        $priceManager->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_2));
        $priceManager->flush();

        $handler = $this->getContainer()->get('oro_pricing.price_list_trigger_handler');
        $this->assertAttributeCount(1, 'scheduledTriggers', $handler);
    }

    /**
     * @param string $productReference
     * @param string $priceListReference
     * @param Price $price
     * @param integer $quantity
     * @return ProductPrice
     */
    protected function getProductPrice($productReference, $priceListReference, Price $price, $quantity)
    {
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.liter');
        /** @var Product $product */
        $product = $this->getReference($productReference);
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);

        $productPrice = new ProductPrice();
        $productPrice
            ->setQuantity($quantity)
            ->setUnit($productUnit)
            ->setProduct($product)
            ->setPriceList($priceList)
            ->setPrice($price);

        return $productPrice;
    }
}
