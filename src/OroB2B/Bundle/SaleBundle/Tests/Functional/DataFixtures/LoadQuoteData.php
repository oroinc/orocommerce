<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class LoadQuoteData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const QUOTE1    = 'sale.quote.1';
    const QUOTE2    = 'sale.quote.2';
    const QUOTE3    = 'sale.quote.3';
    const QUOTE4    = 'sale.quote.4';
    const QUOTE5    = 'sale.quote.5';
    const QUOTE6    = 'sale.quote.6';
    const QUOTE7    = 'sale.quote.7';

    const PRODUCT1  = 'product.1';
    const PRODUCT2  = 'product.2';

    const UNIT1     = 'product_unit.liter';
    const UNIT2     = 'product_unit.bottle';
    const UNIT3     = 'product_unit.box';

    const CURRENCY1 = 'USD';
    const CURRENCY2 = 'EUR';

    /**
     * @var array
     */
    protected $items = [
        [
            'qid'       => self::QUOTE1,
            'products'  => [
                self::PRODUCT1 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity'  => 1,
                        'unit'      => self::UNIT1,
                        'price'     => 1,
                        'currency'  => self::CURRENCY1,
                    ],
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity'  => 2,
                        'unit'      => self::UNIT2,
                        'price'     => 2,
                        'currency'  => self::CURRENCY1,
                    ],
                ],
                self::PRODUCT2 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity'  => 3,
                        'unit'      => self::UNIT3,
                        'price'     => 3,
                        'currency'  => self::CURRENCY1,
                    ]
                ],
            ],
        ],
        [
            'qid'           => self::QUOTE2,
            'account'       => LoadUserData::ACCOUNT1,
            'products'      => [],
        ],
        [
            'qid'           => self::QUOTE3,
            'account'       => LoadUserData::ACCOUNT1,
            'accountUser'   => LoadUserData::ACCOUNT1_USER1,
            'products'      => [],
        ],
        [
            'qid'           => self::QUOTE4,
            'account'       => LoadUserData::ACCOUNT1,
            'accountUser'   => LoadUserData::ACCOUNT1_USER2,
            'products'      => [],
        ],
        [
            'qid'           => self::QUOTE5,
            'account'       => LoadUserData::ACCOUNT1,
            'accountUser'   => LoadUserData::ACCOUNT1_USER3,
            'products'      => [],
        ],
        [
            'qid'           => self::QUOTE6,
            'account'       => LoadUserData::ACCOUNT2,
            'products'      => [],
        ],
        [
            'qid'           => self::QUOTE7,
            'account'       => LoadUserData::ACCOUNT2,
            'accountUser'   => LoadUserData::ACCOUNT2_USER1,
            'products'      => [],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
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

            if (!empty($item['account'])) {
                $quote->setAccount($this->getReference($item['account']));
            }

            if (!empty($item['accountUser'])) {
                $quote->setAccountUser($this->getReference($item['accountUser']));
            }

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
            $productOffer = new QuoteProductOffer();
            $productOffer
                ->setAllowIncrements(false)
                ->setQuantity($item['quantity'])
                ->setPriceType($item['priceType'])
                ->setPrice((new Price())->setValue($item['price'])->setCurrency($item['currency']))
            ;

            if ($this->hasReference($item['unit'])) {
                $productOffer->setProductUnit($this->getReference($item['unit']));
            } else {
                $productOffer->setProductUnitCode($item['unit']);
            }

            $manager->persist($productOffer);

            $product->addQuoteProductOffer($productOffer);
        }

        $manager->persist($product);

        $quote->addQuoteProduct($product);
    }
}
