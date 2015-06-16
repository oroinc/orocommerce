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
    const QUOTE1    = 'sale.quote.1';
    const QUOTE2    = 'sale.quote.2';

    const PRODUCT1  = 'product.1';
    const PRODUCT2  = 'product.2';

    const UNIT1     = 'product_unit.liter';
    const UNIT2     = 'product_unit.bottle';
    const UNIT3     = 'product_unit.box';

    const CURRENCY1 = 'sale.currency.USD';
    const CURRENCY2 = 'sale.currency.EUR';

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
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);

        foreach ($this->items as $item) {
            /* @var $quote Quote */
            $quote = new Quote();
            $quote
                ->setQid($item['qid'])
                ->setOwner($user)
                ->setOrganization($user->getOrganization())
            ;

            foreach ($item['products'] as $sku => $items) {
                $this->addQuoteProduct($manager, $quote, $sku, $items);
            }

            $manager->persist($quote);

            $this->setReference($item['qid'], $quote);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Quote $quote
     * @param string $sku
     * @param array $items
     */
    protected function addQuoteProduct(ObjectManager $manager, Quote $quote, $sku, $items)
    {
        $product = new QuoteProduct();

        if ($this->hasReference($sku)) {
            $product->setProduct($this->getReference($sku));
        } else {
            $product->setProductSku($sku);
        }

        foreach ($items as $item) {
            $productItem = new QuoteProductItem();
            $productItem
                ->setQuantity($item['quantity'])
                ->setPrice((new Price())->setValue($item['price'])->setCurrency($item['currency']))
            ;

            if ($this->hasReference($item['unit'])) {
                $productItem->setProductUnit($this->getReference($item['unit']));
            } else {
                $productItem->setProductUnitCode($item['unit']);
            }

            $manager->persist($productItem);

            $product->addQuoteProductItem($productItem);
        }

        $manager->persist($product);

        $quote->addQuoteProduct($product);
    }
}
