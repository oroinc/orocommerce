<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The data only create the conditions for the correct verification of the combined price lists.
 * When using this fixture, keep in mind that products and prices should not affect the logic of the test
 * and should not participate in the test.
 */
class LoadPriceListWithPricesForCPLBuilderFacade extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const DEFAULT_PRODUCT = 'product-1';
    private const DEFAULT_UNIT = 'kg';
    private const DEFAULT_QUANTITY = 1;
    private const DEFAULT_PRICE = 10;

    public function getDependencies(): array
    {
        return [LoadPriceListRelationsForCPLBuilderFacade::class, LoadProductUnitPrecisions::class];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var PriceManager $priceManager */
        $priceManager = $this->container->get('oro_pricing.manager.price_manager');

        /** @var PriceList[] $priceLists */
        $priceLists = $manager->getRepository(PriceList::class)->findAll();

        foreach ($priceLists as $priceList) {
            $this->generatePrice($priceManager, $priceList);
        }

        $priceManager->flush();
        $manager->flush();
    }

    private function generatePrice(PriceManager $priceManager, PriceList $priceList): void
    {
        $price = Price::create(self::DEFAULT_PRICE, $priceList->getCurrencies()[0]);
        $product = $this->getReference(self::DEFAULT_PRODUCT);
        $productPrice = new ProductPrice();
        $productPrice
            ->setPriceList($priceList)
            ->setUnit((new ProductUnit())->setCode(self::DEFAULT_UNIT))
            ->setQuantity(self::DEFAULT_QUANTITY)
            ->setPrice($price)
            ->setProduct($product);

        $priceManager->persist($productPrice);
    }
}
