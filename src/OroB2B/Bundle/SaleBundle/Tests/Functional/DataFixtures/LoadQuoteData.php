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

    const PRODUCT1  = 'product.1';
    const PRODUCT2  = 'product.2';

    const UNIT1     = 'product_unit.liter';
    const UNIT2     = 'product_unit.bottle';
    const UNIT3     = 'product_unit.box';

    const CURRENCY1 = 'sale-USD';
    const CURRENCY2 = 'sale-EUR';

    /**
     * @var array
     */
    protected $items = [
        [
            'qid'       => self::QUOTE1,
            'products'  => [
                self::PRODUCT1 => [
                    [
                        'quantity'  => 1,
                        'unit'      => self::UNIT1,
                        'price'     => 1,
                        'currency'  => self::CURRENCY1,
                    ],
                    [
                        'quantity'  => 2,
                        'unit'      => self::UNIT2,
                        'price'     => 2,
                        'currency'  => self::CURRENCY1,
                    ],
                ],
                self::PRODUCT2 => [
                    [
                        'quantity'  => 3,
                        'unit'      => self::UNIT3,
                        'price'     => 3,
                        'currency'  => self::CURRENCY1,
                    ]
                ],
            ],
        ],
        [
            'qid'       => self::QUOTE2,
            'products'  => [],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
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

            $this->entityManager->persist($quote);

            $this->setReference($item['qid'], $quote);
        }
        $this->entityManager->flush();
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
