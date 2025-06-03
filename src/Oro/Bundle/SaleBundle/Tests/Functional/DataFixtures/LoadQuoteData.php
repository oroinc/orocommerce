<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsite;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadQuoteData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const QUOTE1 = 'sale.quote.1';
    public const QUOTE2 = 'sale.quote.2';
    public const QUOTE3 = 'sale.quote.3';
    public const QUOTE4 = 'sale.quote.4';
    public const QUOTE5 = 'sale.quote.5';
    public const QUOTE6 = 'sale.quote.6';
    public const QUOTE7 = 'sale.quote.7';
    public const QUOTE8 = 'sale.quote.8';
    public const QUOTE9 = 'sale.quote.9';
    public const QUOTE10 = 'sale.quote.10';
    public const QUOTE11 = 'sale.quote.11';
    public const QUOTE12 = 'sale.quote.12';
    public const QUOTE13 = 'sale.quote.13';
    public const QUOTE_DRAFT = 'sale.quote.draft';
    public const QUOTE_PRICE_CHANGED = 'sale.quote.price_changed';

    public const PRODUCT1 = 'product-1';
    public const PRODUCT2 = 'product-2';

    public const UNIT1 = 'product_unit.liter';
    public const UNIT2 = 'product_unit.bottle';
    public const UNIT3 = 'product_unit.box';

    public const CURRENCY1 = 'USD';
    public const CURRENCY2 = 'EUR';

    public const PRICE1 = 1.00;
    public const PRICE2 = 2.00;

    public static array $items = [
        self::QUOTE1 => [
            'qid' => self::QUOTE1,
            'internal_status' => 'draft',
            'customer_status' => 'open',
            'products' => [
                self::PRODUCT1 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity' => 1,
                        'unit' => self::UNIT1,
                        'price' => self::PRICE1,
                        'currency' => self::CURRENCY1,
                        'allow_increments' => true
                    ],
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity' => 2,
                        'unit' => self::UNIT2,
                        'price' => self::PRICE2,
                        'currency' => self::CURRENCY1,
                        'allow_increments' => false
                    ],
                ],
                self::PRODUCT2 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity' => 3,
                        'unit' => self::UNIT3,
                        'price' => 3,
                        'currency' => self::CURRENCY1,
                        'allow_increments' => false
                    ]
                ],
            ],
            'productRequests' => [
                self::PRODUCT1 => [
                    [
                        'quantity' => 1,
                        'unit' => self::UNIT1,
                        'price' => 1.0,
                        'currency' => self::CURRENCY1
                    ]
                ],
                self::PRODUCT2 => [
                    [
                        'quantity' => 3,
                        'unit' => self::UNIT3,
                        'price' => 2.5,
                        'currency' => self::CURRENCY1
                    ]
                ],
            ],
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'country' => 'US',
                'region' => 'US-IN',
                'city' => 'Romney',
                'street' => '2413 Capitol Avenue',
                'postalCode' => '47981'
            ]
        ],
        self::QUOTE2 => [
            'qid' => self::QUOTE2,
            'internal_status' => 'deleted',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT1,
            'products' => [],
        ],
        self::QUOTE3 => [
            'qid' => self::QUOTE3,
            'internal_status' => 'sent_to_customer',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER1,
            'assignedCustomerUsers' => [LoadUserData::ACCOUNT1_USER1],
            'assignedUsers' => [LoadUserData::USER1],
            'products' => [
                self::PRODUCT1 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity' => 1,
                        'unit' => self::UNIT1,
                        'price' => self::PRICE1,
                        'currency' => self::CURRENCY1,
                        'allow_increments' => true
                    ],
                ],
            ],
            'estimatedShippingCostAmount' => 10,
            'shippingAddress' => [
                'country' => 'US',
                'region' => 'US-NY',
                'city' => 'Rochester',
                'street' => '1215 Caldwell Road',
                'postalCode' => '14608'
            ]
        ],
        self::QUOTE4 => [
            'qid' => self::QUOTE4,
            'internal_status' => 'sent_to_customer',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER2,
            'products' => [],

        ],
        self::QUOTE5 => [
            'qid' => self::QUOTE5,
            'internal_status' => 'open',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER3,
            'validUntil' => 'now',
            'products' => [],
            'shippingAddress' => [
                'customerUserAddress' => 'sale.grzegorz.brzeczyszczykiewicz@example.com.address_1'
            ]
        ],
        self::QUOTE6 => [
            'qid' => self::QUOTE6,
            'internal_status' => 'open',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT2,
            'products' => [],
            'shippingAddress' => [
                'customerAddress' => 'sale.customer.level_1.address_2'
            ]
        ],
        self::QUOTE7 => [
            'qid' => self::QUOTE7,
            'internal_status' => 'open',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT2,
            'customerUser' => LoadUserData::ACCOUNT2_USER1,
            'products' => [],
        ],
        self::QUOTE8 => [
            'qid' => self::QUOTE8,
            'internal_status' => 'open',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER3,
            'expired' => true,
            'products' => [],
        ],
        self::QUOTE9 => [
            'qid' => self::QUOTE9,
            'internal_status' => 'sent_to_customer',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER3,
            'validUntil' => null,
            'products' => [],
            'paymentTerm' => LoadPaymentTermData::TERM_LABEL_NET_10,
        ],
        self::QUOTE10 => [
            'qid' => self::QUOTE10,
            'internal_status' => 'open',
            'customer_status' => 'open',
            'customer' => LoadUserData::PARENT_ACCOUNT,
            'customerUser' => LoadUserData::PARENT_ACCOUNT_USER1,
            'products' => [],
        ],
        self::QUOTE11 => [
            'qid' => self::QUOTE11,
            'internal_status' => 'open',
            'customer_status' => 'open',
            'customer' => LoadUserData::PARENT_ACCOUNT,
            'customerUser' => LoadUserData::PARENT_ACCOUNT_USER2,
            'products' => [],
        ],
        self::QUOTE12 => [
            'qid' => self::QUOTE12,
            'internal_status' => 'sent_to_customer',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER3,
            'validUntil' => null,
            'products' => [],
            'paymentTerm' => LoadPaymentTermData::TERM_LABEL_NET_10,
            'expired' => true
        ],
        self::QUOTE13 => [
            'qid' => self::QUOTE13,
            'internal_status' => 'sent_to_customer',
            'customer_status' => 'open',
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER2,
            'products' => [
                self::PRODUCT1 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity' => 1,
                        'unit' => self::UNIT1,
                        'price' => self::PRICE1,
                        'currency' => self::CURRENCY1,
                        'allow_increments' => false
                    ],
                ],
                self::PRODUCT2 => [
                    [
                        'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                        'quantity' => 100,
                        'unit' => self::UNIT3,
                        'price' => 3,
                        'currency' => self::CURRENCY1,
                        'allow_increments' => true
                    ]
                ],
            ],
            'estimatedShippingCostAmount' => 10
        ],
        self::QUOTE_DRAFT => [
            'qid' => self::QUOTE_DRAFT,
            'internal_status' => 'draft',
            'customer_status' => 'open',
            'customer' => LoadUserData::PARENT_ACCOUNT,
            'customerUser' => LoadUserData::PARENT_ACCOUNT_USER2,
            'products' => [],
        ],
        self::QUOTE_PRICE_CHANGED => [
            'qid' => self::QUOTE_PRICE_CHANGED,
            'internal_status' => 'draft',
            'customer_status' => 'open',
            'customer' => LoadUserData::PARENT_ACCOUNT,
            'customerUser' => LoadUserData::PARENT_ACCOUNT_USER2,
            'products' => [],
            'pricesChanged' => true,
        ],
    ];

    public static function getQuotesFor(string $quoteFieldName, string $quoteFieldValue): array
    {
        return array_filter(self::$items, function ($item) use ($quoteFieldName, $quoteFieldValue) {
            return \array_key_exists($quoteFieldName, $item) && $item[$quoteFieldName] === $quoteFieldValue;
        });
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
            LoadCustomerUserAddresses::class,
            LoadCustomerAddresses::class,
            LoadProductUnitPrecisions::class,
            LoadPaymentTermData::class,
            LoadUser::class,
            LoadWebsite::class
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        /** @var Website $website */
        $website = $this->getReference(LoadWebsite::WEBSITE);

        $paymentTermAssociationProvider = $this->container->get('oro_payment_term.provider.payment_term_association');

        foreach (self::$items as $item) {
            $quote = new Quote();
            $quote->setQid($item['qid']);
            $quote->setWebsite($website);
            $quote->setOwner($user);
            $quote->setOrganization($user->getOrganization());
            $quote->setShipUntil(new \DateTime('+10 day'));
            $quote->setPoNumber('PO_' . strtoupper($item['qid']));
            $quote->setValidUntil($this->getValidUntil($item));
            $quote->setExpired($item['expired'] ?? false);
            $quote->setPricesChanged($item['pricesChanged'] ?? false);

            if (!empty($item['estimatedShippingCostAmount'])) {
                $quote->setEstimatedShippingCostAmount($item['estimatedShippingCostAmount'])->setCurrency('USD');
            }
            if (!empty($item['customer'])) {
                $quote->setCustomer($this->getReference($item['customer']));
            }

            if (!empty($item['customerUser'])) {
                $quote->setCustomerUser($this->getReference($item['customerUser']));
            }
            if (!empty($item['assignedCustomerUsers'])) {
                foreach ($item['assignedCustomerUsers'] as $customerUserRef) {
                    $quote->addAssignedCustomerUser($this->getReference($customerUserRef));
                }
            }
            if (!empty($item['assignedUsers'])) {
                foreach ($item['assignedUsers'] as $userRef) {
                    $quote->addAssignedUser($this->getReference($userRef));
                }
            }

            if (!empty($item['paymentTerm'])) {
                /** @var PaymentTerm $paymentTerm */
                $paymentTerm = $this->getReference(
                    LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . $item['paymentTerm']
                );

                $paymentTermAssociationProvider->setPaymentTerm($quote, $paymentTerm);
            }

            foreach ($item['products'] as $sku => $items) {
                $this->addQuoteProduct($manager, $quote, $sku, $items, $item['productRequests'][$sku] ?? []);
            }

            if (!empty($item['shippingAddress'])) {
                $shippingAddress = $this->createShippingAddress($manager, $item['shippingAddress']);
                $this->setReference($item['qid'] . '.shipping_address', $shippingAddress);
                $quote->setShippingAddress($shippingAddress);
            }

            $manager->persist($quote);

            $this->setReference($item['qid'], $quote);
        }
        $manager->flush();

        // set statuses after autostart workflow
        foreach (self::$items as $item) {
            $quote = $this->getReference($item['qid']);
            $quote->setCustomerStatus(
                $this->getEnumEntity($manager, Quote::CUSTOMER_STATUS_CODE, $item['customer_status'])
            )->setInternalStatus(
                $this->getEnumEntity($manager, Quote::INTERNAL_STATUS_CODE, $item['internal_status'])
            );
            $manager->persist($quote);
        }
        $manager->flush();
    }

    private function addQuoteProduct(
        ObjectManager $manager,
        Quote $quote,
        string $sku,
        array $items,
        array $productRequests
    ): void {
        $product = new QuoteProduct();
        $productReference = $quote->getQid() . '.' . $sku;
        if ($this->hasReference($sku)) {
            $product->setProduct($this->getReference($sku));
        } else {
            $product->setProductSku($sku);
        }

        foreach ($items as $index => $item) {
            $productOffer = new QuoteProductOffer();
            $productOffer->setAllowIncrements($item['allow_increments']);
            $productOffer->setQuantity($item['quantity']);
            $productOffer->setPriceType($item['priceType']);
            $productOffer->setPrice((new Price())->setValue($item['price'])->setCurrency($item['currency']));
            if ($this->hasReference($item['unit'])) {
                $productOffer->setProductUnit($this->getReference($item['unit']));
            } else {
                $productOffer->setProductUnitCode($item['unit']);
            }
            $manager->persist($productOffer);
            $this->addReference($productReference . '.offer.' . ($index + 1), $productOffer);
            $product->addQuoteProductOffer($productOffer);
        }
        foreach ($productRequests as $index => $item) {
            $productRequest = new QuoteProductRequest();
            $productRequest->setQuantity($item['quantity']);
            $productRequest->setPrice((new Price())->setValue($item['price'])->setCurrency($item['currency']));
            if ($this->hasReference($item['unit'])) {
                $productRequest->setProductUnit($this->getReference($item['unit']));
            } else {
                $productRequest->setProductUnitCode($item['unit']);
            }
            $manager->persist($productRequest);
            $this->addReference($productReference . '.request.' . ($index + 1), $productRequest);
            $product->addQuoteProductRequest($productRequest);
        }

        $manager->persist($product);
        $this->addReference($productReference, $product);

        $quote->addQuoteProduct($product);
    }

    private function getEnumEntity(ObjectManager $manager, string $enumCode, string $enumField): EnumOptionInterface
    {
        return $manager->getReference(
            EnumOption::class,
            ExtendHelper::buildEnumOptionId($enumCode, $enumField)
        );
    }

    private function getValidUntil(array $item): ?\DateTime
    {
        if (\array_key_exists('validUntil', $item)) {
            return $item['validUntil'] ? new \DateTime($item['validUntil']) : null;
        }

        return new \DateTime('+10 day');
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function createShippingAddress(ObjectManager $manager, array $data): QuoteAddress
    {
        $address = new QuoteAddress();
        if (isset($data['customerAddress'])) {
            $customerAddress = $this->getReference($data['customerAddress']);
            /** @var QuoteAddressManager $addressManager */
            $addressManager = $this->container->get('oro_sale.manager.quote_address');
            $addressManager->updateFromAbstract($customerAddress, $address);
        } elseif (isset($data['customerUserAddress'])) {
            $customerUserAddress = $this->getReference($data['customerUserAddress']);
            /** @var QuoteAddressManager $addressManager */
            $addressManager = $this->container->get('oro_sale.manager.quote_address');
            $addressManager->updateFromAbstract($customerUserAddress, $address);
        } else {
            if (isset($data['firstName'])) {
                $address->setFirstName($data['firstName']);
            }
            if (isset($data['lastName'])) {
                $address->setLastName($data['lastName']);
            }
            $country = $manager->getReference(Country::class, $data['country']);
            if ($country) {
                $address->setCountry($country);
            } else {
                throw new \RuntimeException('Can\'t find country with ISO ' . $data['country']);
            }
            if (isset($data['region'])) {
                $region = $manager->getReference(Region::class, $data['region']);
                if ($region) {
                    $address->setRegion($region);
                } else {
                    throw new \RuntimeException(sprintf('Can\'t find region with code %s', $data['region']));
                }
            }
            if (isset($data['city'])) {
                $address->setCity($data['city']);
            }
            if (isset($data['street'])) {
                $address->setStreet($data['street']);
            }
            if (isset($data['postalCode'])) {
                $address->setPostalCode($data['postalCode']);
            }
        }

        $manager->persist($address);

        return $address;
    }
}
