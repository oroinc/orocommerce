<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPriceCPLEntityListenerTest extends WebTestCase
{
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
    }

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
        $productPrice = $this->getReference(LoadProductPrices::PRODUCT_PRICE_4);
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
        /** @var PriceListToProductRepository $repository */
        $repository = $em->getRepository(PriceListToProduct::class);
        $repository
            ->createQueryBuilder('pltp')
            ->delete()
            ->getQuery()
            ->execute();

        /** @var ProductPrice $productPrice1 */
        $productPrice1 = $this->getReference(LoadProductPrices::PRODUCT_PRICE_1);
        /** @var ProductPrice $productPrice2 */
        $productPrice2 = $this->getReference(LoadProductPrices::PRODUCT_PRICE_4);
        /** @var ProductPrice $productPrice3 */
        $productPrice3 = $this->getReference(LoadProductPrices::PRODUCT_PRICE_6);

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
