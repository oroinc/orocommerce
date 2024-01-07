<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerUserDemoData;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct as RFPRequestProduct;
use Oro\Bundle\RFPBundle\Migrations\Data\Demo\ORM\LoadRequestDemoData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads demo data for Quote entity.
 */
class LoadQuoteDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    private ?array $productUnits = null;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class,
            LoadProductUnitPrecisionDemoData::class,
            LoadCustomerUserDemoData::class,
            LoadCustomerDemoData::class,
            LoadPriceListDemoData::class,
            LoadRequestDemoData::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        // temporary disable notification rules event listener
        $notificationListener = $this->container->get('oro_workflow.listener.workflow_transition_record');
        $notificationListener->setEnabled(false);

        $user = $this->getUser($manager);
        $requests = $this->getRequests($manager);
        $organization = $user->getOrganization();
        $customers = $this->getCustomers($manager);
        $website = $manager->getRepository(Website::class)->findOneBy(['name' => 'Default']);
        $currencies = $this->getCurrencies();

        for ($i = 0; $i < 20; $i++) {
            /* @var Customer $customer */
            $customer = $customers[mt_rand(0, count($customers) - 1)];

            if (!$customer) {
                $customerUser = null;
            } else {
                $customerUsers = array_merge([null], $customer->getUsers()->getValues());
                /* @var CustomerUser $customerUser */
                $customerUser = $customerUsers[mt_rand(0, count($customerUsers) - 1)];
            }

            // set date in future
            $validUntil = new \DateTime('now');
            $addDays = sprintf('+%s days', mt_rand(10, 100));
            $validUntil->modify($addDays);
            $poNumber = 'CA' . mt_rand(1000, 9999) . 'USD';
            $quote = new Quote();
            $quote
                ->setOwner($user)
                ->setOrganization($organization)
                ->setValidUntil($validUntil)
                ->setCustomerUser($customerUser)
                ->setCustomer($customer)
                ->setShipUntil(new \DateTime('+10 day'))
                ->setPoNumber($poNumber)
                ->setWebsite($website);

            if (1 === mt_rand(1, 3)) {
                $quote->setRequest($requests[mt_rand(1, count($requests) - 1)]);
            }
            $currency = $currencies[random_int(0, count($currencies) - 1)];

            $this->processQuoteProducts($quote, $currency, $manager);

            $manager->persist($quote);
        }

        $manager->flush();

        // enable notification rules event listener after fixtures load
        $notificationListener->setEnabled();
    }

    private function getCustomers(ObjectManager $manager): array
    {
        return array_merge([null], $manager->getRepository(Customer::class)->findBy([], null, 10));
    }

    private function getCurrencies(): array
    {
        return $this->container->get('oro_currency.config.currency')->getCurrencyList();
    }

    private function processQuoteProducts(Quote $quote, string $currency, ObjectManager $manager): void
    {
        $products = $this->getProducts($manager);

        $types = [
            QuoteProduct::TYPE_REQUESTED,
        ];

        $priceTypes = [
            QuoteProductOffer::PRICE_TYPE_UNIT,
        ];

        if ($quote->getRequest()) {
            foreach ($quote->getRequest()->getRequestProducts() as $requestProduct) {
                $type = $types[mt_rand(0, count($types) - 1)];

                $quoteProduct = $this->createQuoteProduct($requestProduct->getProduct(), $type);

                $this->processRequestProductItems($quoteProduct, $requestProduct);

                $quote->addQuoteProduct($quoteProduct);
            }
        } else {
            $numProducts = mt_rand(1, 3);
            for ($i = 0; $i < $numProducts; $i++) {
                $product = $products[mt_rand(1, count($products) - 1)];
                $quote->addQuoteProduct($this->createQuoteProduct($product, QuoteProduct::TYPE_OFFER));
            }
        }

        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $units = $this->getProductUnits($manager, $quoteProduct->getProduct());
            $numProductOffers = mt_rand(1, 3);
            for ($j = 0; $j < $numProductOffers; $j++) {
                if (!count($units)) {
                    continue;
                }

                $productUnit = $units[mt_rand(0, count($units) - 1)];

                $priceType = $priceTypes[mt_rand(0, count($priceTypes) - 1)];

                $quoteProductOffer = new QuoteProductOffer();
                $quoteProductOffer
                    ->setPrice(Price::create(mt_rand(1, 100), $currency))
                    ->setQuantity(mt_rand(1, 100))
                    ->setProductUnit($productUnit)
                    ->setPriceType($priceType)
                    ->setAllowIncrements((bool)mt_rand(0, 1));

                if ($quoteProduct->isTypeNotAvailable()) {
                    $productReplacement = $products[mt_rand(1, count($products) - 1)];
                    $quoteProduct->setProductReplacement($productReplacement);

                    $isFreeFormProductReplacement = mt_rand(0, 1);
                    if ($isFreeFormProductReplacement) {
                        $quoteProduct->setProductReplacement(null);
                    }

                    $unitsRepl = $this->getProductUnits($manager, $quoteProduct->getProductReplacement());
                    $productUnitRepl = $unitsRepl[mt_rand(0, count($unitsRepl) - 1)];
                    $quoteProductOffer->setProductUnit($productUnitRepl);
                }

                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
            }
        }
    }

    private function createQuoteProduct(Product $product, int $type): QuoteProduct
    {
        static $index = 0;

        $index++;

        $quoteProduct = new QuoteProduct();
        $quoteProduct
            ->setProduct($product)
            ->setType($type)
            ->setComment(sprintf('Seller Notes %s', $index + 1))
            ->setCommentCustomer(sprintf('Customer Notes %s', $index + 1));

        $isFreeFormProduct = mt_rand(0, 1);
        if ($isFreeFormProduct) {
            $quoteProduct->setProduct(null);
        }

        return $quoteProduct;
    }

    private function processRequestProductItems(QuoteProduct $quoteProduct, RFPRequestProduct $requestProduct): void
    {
        foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
            $quoteProductRequest = new QuoteProductRequest();
            $quoteProductRequest
                ->setPrice($requestProductItem->getPrice())
                ->setQuantity($requestProductItem->getQuantity())
                ->setProductUnit($requestProductItem->getProductUnit());
            $quoteProduct->addQuoteProductRequest($quoteProductRequest);
        }
    }

    private function getProducts(ObjectManager $manager): array
    {
        $products = $manager->getRepository(Product::class)->findBy([], null, 10);
        if (!$products) {
            throw new \LogicException('There are no products in system');
        }

        return $products;
    }

    private function getRequests(ObjectManager $manager): array
    {
        $requests = $manager->getRepository(RFPRequest::class)->findBy([], null, 10);
        if (!$requests) {
            throw new \LogicException('There are no RFPRequests in system');
        }

        return $requests;
    }

    private function getUser(ObjectManager $manager): User
    {
        $roleRepository = $manager->getRepository(Role::class);
        $role = $roleRepository->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);
        if (!$role) {
            throw new \RuntimeException(sprintf('%s role should exist.', LoadRolesData::ROLE_ADMINISTRATOR));
        }

        $user = $roleRepository->getFirstMatchedUser($role);
        if (!$user) {
            throw new \RuntimeException(
                sprintf('At least one user with role %s should exist.', LoadRolesData::ROLE_ADMINISTRATOR)
            );
        }

        return $user;
    }

    private function getAllUnits(ObjectManager $manager): array
    {
        if (null === $this->productUnits) {
            $this->productUnits = $manager->getRepository(ProductUnit::class)->findBy([], null, 10);
        }

        return $this->productUnits;
    }

    private function getProductUnits(ObjectManager $manager, ?Product $product): array
    {
        if (null === $product) {
            return $this->getAllUnits($manager);
        }

        $productUnits = [];
        foreach ($product->getUnitPrecisions() as $productUnit) {
            $productUnits[] = $productUnit->getUnit();
        }

        return $productUnits;
    }
}
