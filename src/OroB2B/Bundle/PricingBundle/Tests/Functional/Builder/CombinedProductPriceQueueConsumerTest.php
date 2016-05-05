<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;

/**
 * @dbIsolation
 */
class CombinedProductPriceQueueConsumerTest extends WebTestCase
{
    /**
     * @var CombinedProductPriceRepository
     */
    protected $combinedProductPriceRepository;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            ]
        );
        $this->registry = $this->getContainer()->get('doctrine');
        $this->combinedProductPriceRepository = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository('OroB2BPricingBundle:CombinedProductPrice');
    }

    /**
     * @dataProvider processDataProvider
     * @param array $priceData
     * @param Price $newPrice
     * @param array $CPLs
     */
    public function testProcess(array $priceData, Price $newPrice, array $CPLs)
    {
        $priceData['product'] = $this->getReference($priceData['product']);
        $priceData['priceList'] = $this->getReference($priceData['priceList']);
        $priceData['unit'] = $this->getReference($priceData['unit']);

        $this->changeProductPrice($priceData, $newPrice);
        $this->getContainer()->get('orob2b_pricing.builder.combined_product_price_queue_consumer')->process();

        foreach ($CPLs as $combinedPriceListReference) {
            $this->assertTrue(
                $this->compareCombinedProductPrices(
                    $priceData,
                    $newPrice,
                    $combinedPriceListReference
                )
            );
        }
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            [
                'priceData' => [
                    'product' => 'product.1',
                    'priceList' => 'price_list_1',
                    'quantity' => 1,
                    'unit' => 'product_unit.liter',
                ],
                'newPrice' => Price::create(1, 'USD'),
                'combinedPriceLists' => ['1f'],
            ],
            [
                'priceData' => [
                    'product' => 'product.2',
                    'priceList' => 'price_list_1',
                    'quantity' => 1,
                    'unit' => 'product_unit.liter',
                ],
                'newPrice' => Price::create(3, 'USD'),
                'combinedPriceLists' => ['1f'],
            ],
        ];
    }

    /**
     * @param array $priceData
     * @param Price $newPrice
     */
    protected function changeProductPrice(array $priceData, Price $newPrice)
    {
        $manager = $this->registry->getManagerForClass('OroB2B\Bundle\PricingBundle\Entity\ProductPrice');
        /** @var ProductPrice $productPrice */
        $productPrice = $manager->getRepository('OroB2B\Bundle\PricingBundle\Entity\ProductPrice')
            ->findOneBy($priceData);

        if (!$productPrice) {
            $productPrice = new ProductPrice();
            $productPrice->setPriceList($priceData['priceList']);
            $productPrice->setQuantity($priceData['quantity']);
            $productPrice->setUnit($priceData['unit']);
            $productPrice->setProduct($priceData['product']);
            $productPrice->setPrice($newPrice);
            $manager->persist($productPrice);
        }
        $productPrice->setPrice($newPrice);
        $manager->flush();
    }

    /**
     * @param $priceData
     * @param Price $newPrice
     * @param $cplReference
     * @return bool
     */
    protected function compareCombinedProductPrices($priceData, Price $newPrice, $cplReference)
    {
        $combinedPriceList = $this->getReference($cplReference);
        $priceData['priceList'] = $combinedPriceList;
        /** @var CombinedProductPrice $combinedPrice */
        $combinedPrice = $this->combinedProductPriceRepository->findOneBy($priceData);

        if ($combinedPrice) {
            return $combinedPrice->getPrice()->getCurrency() === $newPrice->getCurrency()
                && $combinedPrice->getPrice()->getValue() == $newPrice->getValue();
        }

        return false;
    }
}
