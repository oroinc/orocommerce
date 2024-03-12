<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\ProductPriceReference;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPriceEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;
    use ProductPriceReference;

    private PriceList $defaultPriceList;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductPrices::class, LoadPriceRuleLexemes::class]);

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');

        $registry = $this->getContainer()->get('doctrine');
        $this->defaultPriceList = $registry
            ->getRepository(PriceList::class)
            ->findOneBy(['name' => 'Default Price List']);

        self::enableMessageBuffering();
    }

    private function getPriceManager(): PriceManager
    {
        return self::getContainer()->get('oro_pricing.manager.price_manager');
    }

    public function testPostPersist()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);

        $price = new ProductPrice();
        $price->setProduct($product);
        $price->setPriceList($this->defaultPriceList);
        $price->setQuantity(1);
        $price->setUnit($this->getReference('product_unit.box'));
        $price->setPrice(Price::create(42, 'USD'));

        $priceManager = $this->getPriceManager();
        $priceManager->persist($price);
        $priceManager->flush();

        // Additional message for self price list is sent because PriceListToProduct relation was created
        // And price rules for self price list may be affected.
        $this->assertPriceRuleMessagesSent($product, [$this->defaultPriceList->getId()]);

        return $price;
    }

    public function testPreUpdate()
    {
        $priceManager = $this->getPriceManager();

        /** @var ProductPrice $price */
        $price = $this->getReference(LoadProductPrices::PRODUCT_PRICE_13);
        $product = $price->getProduct();

        $price->setPrice(Price::create(17.5, 'EUR'));
        $price->setQuantity(1);

        $priceManager->persist($price);
        $priceManager->flush();

        //Ensure that no messages sent to MQ if no price changes
        self::assertMessagesCount(ResolvePriceRulesTopic::getName(), 0);

        $price->setPrice(Price::create($price->getPrice()->getValue() + 0.1, 'EUR'));
        $price->setQuantity(20);

        $priceManager->persist($price);
        $priceManager->flush();

        $this->assertPriceRuleMessagesSent($product);
    }

    public function testPreRemove()
    {
        /** @var ProductPrice $price */
        $price = $this->getReference(LoadProductPrices::PRODUCT_PRICE_13);
        $product = $price->getProduct();

        $priceManager = $this->getPriceManager();
        $priceManager->remove($price);
        $priceManager->flush();

        $this->assertPriceRuleMessagesSent($product);
    }

    private function assertPriceRuleMessagesSent(Product $product, array $additionalPriceLists = []): void
    {
        // Collect affected price lists from lexemes.
        /** @var PriceRuleLexeme[] $lexemes */
        $lexemes = $this->getPriceManager()->getEntityManager()
            ->getRepository(PriceRuleLexeme::class)
            ->findBy(['relationId' => $this->defaultPriceList->getId()]);

        $affectedPriceListIds = [];
        foreach ($additionalPriceLists as $additionalPriceListId) {
            $affectedPriceListIds[$additionalPriceListId] = true;
        }
        foreach ($lexemes as $lexeme) {
            $affectedPriceListIds[$lexeme->getPriceList()->getId()] = true;
        }
        $expectedMessage = array_fill_keys(array_keys($affectedPriceListIds), [$product->getId()]);

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => $expectedMessage
            ]
        );
    }
}
