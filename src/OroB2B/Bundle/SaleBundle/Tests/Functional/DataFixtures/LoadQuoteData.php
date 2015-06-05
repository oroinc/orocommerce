<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;

class LoadQuoteData extends AbstractFixture implements DependentFixtureInterface
{
    const QUOTE1    = 'sale-quote1';
    const QUOTE2    = 'sale-quote2';

    /**
     * @var array
     */
    protected $items = [
        [
            'qid'       => self::QUOTE1,
            'products'  => [
                LoadProductData::PRODUCT1 => [
                    [
                        'quantity'     => 1,
                        'unit'      => LoadProductData::UNIT1,
                        'price'     => 1,
                        'currency'  => LoadProductData::CURRENCY1,
                    ],
                    [
                        'quantity'     => 2,
                        'unit'      => LoadProductData::UNIT2,
                        'price'     => 2,
                        'currency'  => LoadProductData::CURRENCY1,
                    ],
                ],
                LoadProductData::PRODUCT2 => [
                    [
                        'quantity'     => 3,
                        'unit'      => LoadProductData::UNIT3,
                        'price'     => 3,
                        'currency'  => LoadProductData::CURRENCY1,
                    ]
                ],
                LoadProductData::PRODUCT3 => [],
            ],
        ],
        [
            'qid'       => self::QUOTE2,
            'products'  => [
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadProductData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $em = $this->entityManager;

        foreach ($this->items as $item) {
            /* @var $quote Quote */
            $quote = new Quote();
            $quote
                ->setQid($item['qid'])
            ;

            foreach ($this->getQuoteProducts($item['products']) as $product) {
                /* @var $product QuoteProduct */
                $quote
                    ->addQuoteProduct($product)
                ;
            }

            $em->persist($quote);

            $this->setReference($item['qid'], $quote);
        }
        $em->flush();
    }

    /**
     * @param array $data
     * @return array|QuoteProduct[]
     */
    protected function getQuoteProducts($data)
    {
        $products = [];

        foreach ($data as $sku => $items) {
            $products[] = $this->getQuoteProduct($sku, $items);
        }

        return $products;
    }

    /**
     * @param string $sku
     * @param array $items
     * @return QuoteProduct
     */
    protected function getQuoteProduct($sku, $items)
    {
        $em = $this->entityManager;

        $product = new QuoteProduct();
        $product
            ->setProduct($this->getReference($sku))
        ;

        foreach ($items as $item) {
            $productItem = new QuoteProductItem();
            $productItem
                ->setQuantity($item['quantity'])
                ->setProductUnit($this->getReference($item['unit']))
                ->setPrice((new Price())->setValue($item['price'])->setCurrency($item['currency']))
            ;

            $em->persist($productItem);

            $product
                ->addQuoteProductItem($productItem)
            ;
        }

        $em->persist($product);

        $em->flush();

        return $product;
    }
}
