<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadPriceListForUnitTesting extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadProductData::class,
            LoadProductUnits::class,
            LoadOrganization::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $pl1 = $this->createPriceList('pricelist.without_rule', $manager);
        $pl2 = $this->createPriceList('pricelist.with_rule', $manager);

        $this->assignProductToPriceList($this->getReference(LoadProductData::PRODUCT_1), $pl1, $manager);
        $this->assignProductToPriceList($this->getReference(LoadProductData::PRODUCT_1), $pl2, $manager);

        $this->createPriceRule('pricelist2.pricerule1', $pl1, $pl2, $manager);
        $manager->flush();

        $this->container->get('oro_pricing.handler.price_rule_lexeme_handler')->updateLexemes($pl2);

        $this->createProductPrice(
            $pl1,
            2,
            10,
            $this->getReference(LoadProductUnits::BOX),
            $this->getReference(LoadProductData::PRODUCT_1)
        );
    }

    private function createPriceList($name, ObjectManager $manager): PriceList
    {
        $priceList = new PriceList();

        $priceList->setName($name)
            ->setCurrencies(['USD'])
            ->setActive(true)
            ->setOrganization($this->getReference('organization'));

        $manager->persist($priceList);
        $this->setReference($name, $priceList);

        return $priceList;
    }

    private function createPriceRule(
        string $reference,
        PriceList $sourcePl,
        PriceList $priceList,
        ObjectManager $manager
    ) {
        $priceRule = new PriceRule();

        $priceRule
            ->setQuantityExpression("pricelist[{$sourcePl->getId()}].prices.quantity/3")
            ->setCurrencyExpression("pricelist[{$sourcePl->getId()}].prices.currency")
            ->setProductUnitExpression("pricelist[{$sourcePl->getId()}].prices.unit")
            ->setRule("pricelist[{$sourcePl->getId()}].prices.value * 10")
            ->setPriority(1)
            ->setPriceList($priceList);

        $manager->persist($priceRule);
        $this->setReference($reference, $priceRule);
    }

    private function createProductPrice(
        PriceList $priceList,
        float $priceValue,
        float $qty,
        ProductUnit $unit,
        Product $product
    ): ProductPrice {
        $priceManager = $this->container->get('oro_pricing.manager.price_manager');

        $price = Price::create($priceValue, 'USD');
        $productPrice = new ProductPrice();
        $productPrice
            ->setPriceList($priceList)
            ->setUnit($unit)
            ->setQuantity($qty)
            ->setPrice($price)
            ->setProduct($product);

        $priceManager->persist($productPrice);
        $priceManager->flush();

        return $productPrice;
    }

    private function assignProductToPriceList(Product $product, PriceList $pl1, ObjectManager $manager)
    {
        $priceListToProduct = new PriceListToProduct();
        $priceListToProduct->setProduct($product)
            ->setPriceList($pl1);

        $manager->persist($priceListToProduct);
    }
}
