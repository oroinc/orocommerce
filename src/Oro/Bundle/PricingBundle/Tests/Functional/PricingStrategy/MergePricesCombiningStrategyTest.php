<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\PricingStrategy;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesForCombination;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Component\MessageQueue\Client\Message;

class MergePricesCombiningStrategyTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var MergePricesCombiningStrategy */
    protected $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadProductPricesForCombination::class,
            LoadCombinedPriceLists::class
        ]);

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');

        $this->resolver = $this->getContainer()->get('oro_pricing.pricing_strategy.strategy_register')
            ->get(MergePricesCombiningStrategy::NAME);
    }

    public function testEmptyPriceLists()
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('1e');
        $now = new \DateTime();

        $this->resolver->combinePrices($combinedPriceList, [], $now->getTimestamp());
        $actualPrices = $this->getCombinedPrices($combinedPriceList);

        $this->assertEquals([], $actualPrices);
    }

    /**
     * @dataProvider combinePricesDataProvider
     * @param string $combinedPriceList
     * @param array $expectedPrices
     */
    public function testCombinePrices($combinedPriceList, array $expectedPrices)
    {
        $collector = self::getMessageCollector();
        $collector->clear();
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $now = new \DateTime();

        // second call to check avoiding of same recalculation
        $this->resolver->combinePrices($combinedPriceList, [], $now->getTimestamp());
        $this->resolver->combinePrices($combinedPriceList, [], $now->getTimestamp());

        $actualPrices = $this->getCombinedPrices($combinedPriceList);
        $this->assertEquals($expectedPrices, $actualPrices);

        $messages = $collector->getTopicSentMessages(AsyncIndexer::TOPIC_REINDEX);
        $this->assertCount(1, $messages);

        /** @var Message $message */
        $message = $messages[0]['message'];
        $this->assertInstanceOf(Message::class, $message);

        $this->assertEquals([Product::class], $message->getBody()['class']);
        $products = [];

        $productKeys = array_keys($expectedPrices);
        foreach ($productKeys as $productKey) {
            $product = $this->getReference($productKey);
            $products[] = $product->getId();
        }

        $products = array_unique($products);
        $messageProductIds = $message->getBody()['context']['entityIds'];
        sort($messageProductIds);
        sort($products);
        $this->assertEquals($products, $messageProductIds);
    }

    /**
     * @return array
     */
    public function combinePricesDataProvider()
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle'
                    ]
                ],
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product-2' => [
                        '10 USD/10 bottle'
                    ]
                ],
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle'
                    ]
                ],
            ]
        ];
    }

    /**
     * @depends testCombinePrices
     * @dataProvider addedCombinePricesDataProvider
     * @param string $combinedPriceList
     * @param array $expectedPrices
     */
    public function testCombinePricesByProductPriceAdded($combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->resolver->combinePrices($combinedPriceList);
        $this->assertTrue($combinedPriceList->isPricesCalculated());
        $this->assertNotEmpty($this->getCombinedPrices($combinedPriceList));
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        /** @var Product $product */
        $product = $this->getReference('product-2');
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');
        $price = Price::create(42, 'EUR');
        $productPrice = new ProductPrice();
        $productPrice->setProduct($product)
            ->setPriceList($priceList)
            ->setPrice($price)
            ->setQuantity(1)
            ->setUnit($unit);
        $priceManager->persist($productPrice);
        $priceManager->flush();

        $combinedPriceList->setPricesCalculated(false);
        $this->resolver->combinePrices($combinedPriceList, [$product->getId()]);
        $this->assertFalse($combinedPriceList->isPricesCalculated());
        $actualPrices = $this->getCombinedPrices($combinedPriceList);

        $priceManager->remove($productPrice);
        $priceManager->flush();

        $this->assertEquals($expectedPrices, $actualPrices);
    }

    /**
     * @return array
     */
    public function addedCombinePricesDataProvider()
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '42 EUR/1 liter',
                        '10 USD/10 bottle',
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '42 EUR/1 liter',
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product-2' => [
                        '42 EUR/1 liter',
                    ]
                ]
            ]
        ];
    }

    /**
     * @depends testCombinePricesByProductPriceAdded
     * @dataProvider updatedCombinePricesDataProvider
     * @param string $combinedPriceList
     * @param array $expectedPrices
     */
    public function testCombinePricesByProductPriceUpdate($combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->resolver->combinePrices($combinedPriceList);
        $this->assertNotEmpty($this->getCombinedPrices($combinedPriceList));

        /** @var ProductPrice $price */
        $price = $this->getPriceByReference('product_price.7');
        $price->getPrice()->setValue(22);
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->persist($price);
        $priceManager->flush();

        $this->resolver->combinePrices($combinedPriceList, [$price->getProduct()->getId()]);

        $actualPrices = $this->getCombinedPrices($combinedPriceList);
        $this->assertEquals($expectedPrices, $actualPrices);
    }

    /**
     * @return array
     */
    public function updatedCombinePricesDataProvider()
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '22 USD/10 bottle'
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product-2' => [
                        '22 USD/10 bottle'
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '22 USD/10 bottle'
                    ]
                ]
            ]
        ];
    }

    /**
     * @depends testCombinePricesByProductPriceUpdate
     * @dataProvider removedCombinePricesDataProvider
     * @param string $combinedPriceList
     * @param array $expectedPrices
     */
    public function testCombinePricesByProductPriceRemoved($combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->resolver->combinePrices($combinedPriceList);
        $this->assertNotEmpty($this->getCombinedPrices($combinedPriceList));

        /** @var Product $product */
        $product = $this->getReference('product-2');
        /** @var ProductPrice $price */
        $price = $this->getPriceByReference('product_price.7');
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->remove($price);
        $priceManager->flush();

        $this->resolver->combinePrices($combinedPriceList, [$product->getId()]);

        $actualPrices = $this->getCombinedPrices($combinedPriceList);
        $this->assertEquals($expectedPrices, $actualPrices);

        //recreate price for next test
        $price->setId(null);
        $priceManager->persist($price);
        $priceManager->flush();
    }

    /**
     * @return array
     */
    public function removedCombinePricesDataProvider()
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product-2' => [
                        '1 USD/1 bottle'
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle'
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                    ]
                ]
            ]
        ];
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return array
     */
    protected function getCombinedPrices(CombinedPriceList $combinedPriceList)
    {
        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:CombinedProductPrice')
            ->getRepository('OroPricingBundle:CombinedProductPrice');

        /** @var CombinedProductPrice[] $prices */
        $prices = $repository->findBy(
            ['priceList' => $combinedPriceList],
            ['product' => 'ASC', 'quantity' => 'ASC', 'value' => 'ASC', 'currency' => 'ASC']
        );

        return $this->formatPrices($prices);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param array|ProductPrice[] $prices
     * @return array
     */
    protected function formatPrices(array $prices)
    {
        $actualPrices = [];
        foreach ($prices as $price) {
            $actualPrices[$price->getProduct()->getSku()][] = sprintf(
                '%d %s/%d %s',
                $price->getPrice()->getValue(),
                $price->getPrice()->getCurrency(),
                $price->getQuantity(),
                $price->getProductUnitCode()
            );
        }

        return $actualPrices;
    }

    /**
     * @param $reference
     * @return null|BaseProductPrice
     */
    protected function getPriceByReference($reference)
    {
        $criteria = LoadProductPricesForCombination::$data[$reference];
        /** @var ProductPriceRepository $repository */
        $registry = $this->getContainer()->get('doctrine');
        $repository = $registry->getRepository(ProductPrice::class);
        /** @var Product $product */
        $criteria['product'] = $this->getReference($criteria['product']);
        if ($criteria['priceList'] === 'default_price_list') {
            $criteria['priceList'] = $registry->getManager()->getRepository('OroPricingBundle:PriceList')->getDefault();
        } else {
            /** @var PriceList $priceList */
            $criteria['priceList'] = $this->getReference($criteria['priceList']);
        }
        /** @var ProductUnit $unit */
        $criteria['unit'] = $this->getReference($criteria['unit']);
        unset($criteria['value']);
        $prices = $repository->findByPriceList(
            $this->getContainer()->get('oro_pricing.shard_manager'),
            $criteria['priceList'],
            $criteria
        );

        return isset($prices[0]) ? $prices[0] : null;
    }
}
