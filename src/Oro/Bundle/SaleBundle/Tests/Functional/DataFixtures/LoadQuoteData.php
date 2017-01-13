<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadQuoteData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const QUOTE1    = 'sale.quote.1';
    const QUOTE2    = 'sale.quote.2';
    const QUOTE3    = 'sale.quote.3';
    const QUOTE4    = 'sale.quote.4';
    const QUOTE5    = 'sale.quote.5';
    const QUOTE6    = 'sale.quote.6';
    const QUOTE7    = 'sale.quote.7';
    const QUOTE8    = 'sale.quote.8';
    const QUOTE9    = 'sale.quote.9';
    const QUOTE10    = 'sale.quote.10';
    const QUOTE11    = 'sale.quote.11';

    const PRODUCT1  = 'product.1';
    const PRODUCT2  = 'product.2';

    const UNIT1     = 'product_unit.liter';
    const UNIT2     = 'product_unit.bottle';
    const UNIT3     = 'product_unit.box';

    const CURRENCY1 = 'USD';
    const CURRENCY2 = 'EUR';

    const PRICE1 = 1.00;
    const PRICE2 = 2.00;

    /**
     * @var array
     */
    public static $items = [
        self::QUOTE1 => [
            'qid'       => self::QUOTE1,
            'products'  => [
                self::PRODUCT1 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity'  => 1,
                        'unit'      => self::UNIT1,
                        'price'     => self::PRICE1,
                        'currency'  => self::CURRENCY1,
                        'allow_increments' => true
                    ],
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity'  => 2,
                        'unit'      => self::UNIT2,
                        'price'     => self::PRICE2,
                        'currency'  => self::CURRENCY1,
                        'allow_increments' => false
                    ],
                ],
                self::PRODUCT2 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity'  => 3,
                        'unit'      => self::UNIT3,
                        'price'     => 3,
                        'currency'  => self::CURRENCY1,
                        'allow_increments' => false
                    ]
                ],
            ],
        ],
        self::QUOTE2 => [
            'qid'           => self::QUOTE2,
            'customer'       => LoadUserData::ACCOUNT1,
            'products'      => [],
        ],
        self::QUOTE3 => [
            'qid'           => self::QUOTE3,
            'customer'       => LoadUserData::ACCOUNT1,
            'customerUser'   => LoadUserData::ACCOUNT1_USER1,
            'products'      => [
                self::PRODUCT1 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity'  => 1,
                        'unit'      => self::UNIT1,
                        'price'     => self::PRICE1,
                        'currency'  => self::CURRENCY1,
                        'allow_increments' => true
                    ],
                ],
            ],
            'estimatedShippingCostAmount' => 10
        ],
        self::QUOTE4 => [
            'qid'           => self::QUOTE4,
            'customer'       => LoadUserData::ACCOUNT1,
            'customerUser'   => LoadUserData::ACCOUNT1_USER2,
            'products'      => [],

        ],
        self::QUOTE5 => [
            'qid'           => self::QUOTE5,
            'customer'       => LoadUserData::ACCOUNT1,
            'customerUser'   => LoadUserData::ACCOUNT1_USER3,
            'validUntil'    => 'now',
            'products'      => [],
        ],
        self::QUOTE6 => [
            'qid'           => self::QUOTE6,
            'customer'       => LoadUserData::ACCOUNT2,
            'products'      => [],
        ],
        self::QUOTE7 => [
            'qid'           => self::QUOTE7,
            'customer'       => LoadUserData::ACCOUNT2,
            'customerUser'   => LoadUserData::ACCOUNT2_USER1,
            'products'      => [],
        ],
        self::QUOTE8 => [
            'qid'           => self::QUOTE8,
            'customer'       => LoadUserData::ACCOUNT1,
            'customerUser'   => LoadUserData::ACCOUNT1_USER3,
            'expired'       => true,
            'products'      => [],
        ],
        self::QUOTE9 => [
            'qid'           => self::QUOTE9,
            'customer'       => LoadUserData::ACCOUNT1,
            'customerUser'   => LoadUserData::ACCOUNT1_USER3,
            'validUntil'    => null,
            'products'      => [],
            'paymentTerm'   => LoadPaymentTermData::TERM_LABEL_NET_10,
        ],
        self::QUOTE10 => [
            'qid'           => self::QUOTE10,
            'customer'       => LoadUserData::PARENT_ACCOUNT,
            'customerUser'   => LoadUserData::PARENT_ACCOUNT_USER1,
            'products'      => [],
        ],
        self::QUOTE11 => [
            'qid'           => self::QUOTE11,
            'customer'       => LoadUserData::PARENT_ACCOUNT,
            'customerUser'   => LoadUserData::PARENT_ACCOUNT_USER2,
            'products'      => [],
        ],
    ];

    /**
     * @param string $quoteFieldName
     * @param string $quoteFieldValue
     * @return array
     */
    public static function getQuotesFor($quoteFieldName, $quoteFieldValue)
    {
        return array_filter(self::$items, function ($item) use ($quoteFieldName, $quoteFieldValue) {
            return array_key_exists($quoteFieldName, $item) && $item[$quoteFieldName] == $quoteFieldValue;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadUserData::class,
            LoadCustomerUserAddresses::class,
            LoadCustomerAddresses::class,
            LoadProductUnitPrecisions::class,
            LoadPaymentTermData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);
        /** @var Website $website */
        $website = $manager->getRepository(Website::class)->findOneBy(['default' => true]);

        $paymentTermAssociationProvider = $this->container->get('oro_payment_term.provider.payment_term_association');

        foreach (self::$items as $item) {
            $poNumber = 'CA' . mt_rand(1000, 9999) . 'USD';

            /* @var $quote Quote */
            $quote = new Quote();
            $quote
                ->setQid($item['qid'])
                ->setWebsite($website)
                ->setOwner($user)
                ->setOrganization($user->getOrganization())
                ->setShipUntil(new \DateTime('+10 day'))
                ->setPoNumber($poNumber)
                ->setValidUntil($this->getValidUntil($item))
                ->setExpired(array_key_exists('expired', $item) ? $item['expired'] : false);

            if (!empty($item['estimatedShippingCostAmount'])) {
                $quote->setEstimatedShippingCostAmount($item['estimatedShippingCostAmount'])->setCurrency('USD');
            }
            if (!empty($item['customer'])) {
                $quote->setCustomer($this->getReference($item['customer']));
            }

            if (!empty($item['customerUser'])) {
                $quote->setCustomerUser($this->getReference($item['customerUser']));
            }

            if (!empty($item['paymentTerm'])) {
                /** @var PaymentTerm $paymentTerm */
                $paymentTerm = $this->getReference(
                    LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX.$item['paymentTerm']
                );

                $paymentTermAssociationProvider->setPaymentTerm($quote, $paymentTerm);
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

        foreach ($items as $index => $item) {
            $productOffer = new QuoteProductOffer();
            $productOffer
                ->setAllowIncrements($item['allow_increments'])
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

            // e.g sale.quote.1.product.1.offer.1
            $this->addReference($quote->getQid() . '.' . $sku . '.offer.' . ($index + 1), $productOffer);

            $product->addQuoteProductOffer($productOffer);
        }

        $manager->persist($product);

        $quote->addQuoteProduct($product);
    }

    /**
     * @param array $item
     * @return \DateTime|null
     */
    protected function getValidUntil(array $item)
    {
        return array_key_exists('validUntil', $item)
            ? ($item['validUntil'] ? new \DateTime($item['validUntil']) : null)
            : new \DateTime('+10 day');
    }
}
