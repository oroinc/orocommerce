<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCombinedPriceListsForActivationPlan extends AbstractCombinedPriceListsFixture implements
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const DEFAULT_PRODUCT = 'product-1';
    private const DEFAULT_UNIT = 'kg';
    private const DEFAULT_QUANTITY = 1;
    private const DEFAULT_PRICE = 10;

    /**
     * @var array
     */
    protected $data = [
        [
            'name' => '1t_2t_3t',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [LoadWebsiteData::WEBSITE1],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'mergeAllowed' => true,
                ],
            ],
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

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

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPriceLists::class,
            LoadWebsiteData::class,
            LoadGroups::class,
            LoadProductUnitPrecisions::class
        ];
    }
}
