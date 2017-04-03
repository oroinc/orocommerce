<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\ProductPriceReference;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPriceEntityListenerTest extends WebTestCase
{
    use MessageQueueTrait,
        ProductPriceReference;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->cleanScheduledMessages();
    }

    public function testPostPersist()
    {
        /** @var EntityManagerInterface $priceManager */
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');

        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);

        $price = new ProductPrice();
        $price->setProduct($product)
            ->setPriceList($priceList)
            ->setQuantity(1)
            ->setUnit($this->getReference('product_unit.box'))
            ->setPrice(Price::create(42, 'USD'));

        $priceManager->persist($price);
        $priceManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }

    public function testPreUpdate()
    {
        /** @var PriceManager $priceManager */
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        $shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        $em = $priceManager->getEntityManager();
        $repository = $em->getRepository(ProductPrice::class);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $prices = $repository->findByPriceList(
            $shardManager,
            $priceList,
            ['product' => $product, 'priceList' => $priceList, 'currency' => 'USD', 'quantity' => 10]
        );
        /** @var ProductPrice $price */
        $price = $prices[0];
        $price->setPrice(Price::create(12.2, 'USD'));
        $price->setQuantity(20);

        $priceManager->persist($price);
        $em->flush();

        $this->sendScheduledMessages();
        $a = self::getMessageCollector()->getSentMessages();

        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $priceList->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $priceManager */
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var ProductPrice $price */
        $price = $this->getPriceByReference('product_price.2');
        $priceManager->remove($price);
        $priceManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }
}
