<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerUserDemoData;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadProductPriceDemoData;
use Oro\Bundle\PricingBundle\Model\AbstractPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM\LoadShoppingListDemoData;
use Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM\LoadTaxConfigurationDemoData;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads demo data for orders.
 */
class LoadOrderDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    private array $countries = [];
    private array $regions = [];
    private array $users = [];
    private array $orderInternalStatuses = [];
    private array $orderShippingStatuses = [];
    private array $paymentTerms = [];
    private array $websites = [];
    private array $lineItemData = [];
    private array $products = [];
    private array $priceLists = [];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCustomerDemoData::class,
            LoadCustomerUserDemoData::class,
            LoadPaymentTermDemoData::class,
            LoadPriceListDemoData::class,
            LoadProductPriceDemoData::class,
            LoadShoppingListDemoData::class,
            LoadTaxConfigurationDemoData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EntityManagerInterface $manager */

        $this->toggleFeatures(false);
        $this->toggleListeners(false);
        $this->disablePrePersistCallback($manager->getClassMetadata(Order::class));

        /** @var User $user */
        $user = $manager->getRepository(User::class)->findOneBy([]);

        /** @var CustomerUser $customerUser */
        $customerUser = $manager->getRepository(CustomerUser::class)->findOneBy(['isGuest' => false]);
        /** @var CustomerUser $guestCustomerUser */
        $guestCustomerUser = $manager->getRepository(CustomerUser::class)->findOneBy(['isGuest' => true]);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $manager->getRepository(ShoppingList::class)->findOneBy([]);

        /** @var RateConverterInterface $rateConverter */
        $rateConverter = $this->container->get('oro_currency.converter.rate');
        /** @var TotalHelper $totalHelper */
        $totalHelper = $this->container->get('oro_order.order.total.total_helper');
        /** @var PaymentTermAssociationProvider $paymentTermAccessor */
        $paymentTermAccessor = $this->container->get('oro_payment_term.provider.payment_term_association');

        $handler = fopen($this->getDataFilePath('@OroOrderBundle/Migrations/Data/Demo/ORM/data/orders.csv'), 'r');
        $headers = fgetcsv($handler, 1000, ',');
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $customerUser = $row['isGuest'] ? $guestCustomerUser : $customerUser;
            $createdAt = $this->getRandomDateTime();

            $order = new Order();
            $order->setOwner($user);
            $order->setCreatedBy(!empty($row['createdBy']) ? $this->getUser($row['createdBy'], $manager) : null);
            $order->setCustomer($customerUser->getCustomer());
            $order->setIdentifier($row['identifier']);
            $order->setCustomerUser($customerUser);
            $order->setOrganization($user->getOrganization());
            $order->setBillingAddress(
                $this->createOrderAddress($manager, $this->getBillingAddressData($row, $customerUser))
            );
            $order->setShippingAddress(
                $this->createOrderAddress($manager, $this->getShippingAddressData($row, $customerUser))
            );
            $order->setWebsite($this->getWebsite($manager, $row['websiteName']));
            $order->setShipUntil(new \DateTime());
            $order->setCurrency($row['currency']);
            $order->setPoNumber($row['poNumber']);
            $order->setTotalObject($this->createMultiCurrency($row['total'], $row['currency'], $rateConverter));
            $order->setSubtotalObject($this->createMultiCurrency($row['subtotal'], $row['currency'], $rateConverter));
            $order->setInternalStatus($this->getOrderInternalStatusByName($row['internalStatus'], $manager));
            $order->setShippingStatus($this->getOrderShippingStatusByName($row['shippingStatus'], $manager));
            $order->setCreatedAt($createdAt);
            $order->setUpdatedAt(clone $createdAt);

            $paymentTermAccessor->setPaymentTerm($order, $this->getPaymentTerm($manager, $row['paymentTerm']));

            $this->addOrderLineItems($order, $manager);

            if (ShoppingList::class === $row['sourceEntityClass']) {
                $order->setSourceEntityClass($row['sourceEntityClass']);
                $order->setSourceEntityId($shoppingList->getId());
            }

            if (!empty($row['customerNotes'])) {
                $order->setCustomerNotes($row['customerNotes']);
            }

            $manager->persist($order);

            $totalHelper->fill($order);
        }
        fclose($handler);

        $manager->flush();

        $this->toggleFeatures(true);
        $this->toggleListeners(true);
        $this->enablePrePersistCallback($manager->getClassMetadata(Order::class));

        $this->countries = [];
        $this->regions = [];
        $this->users = [];
        $this->orderInternalStatuses = [];
        $this->orderShippingStatuses = [];
        $this->paymentTerms = [];
        $this->websites = [];
        $this->lineItemData = [];
        $this->products = [];
        $this->priceLists = [];
    }

    private function getDataFilePath(string $file): string
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate($file);
        if (\is_array($filePath)) {
            $filePath = current($filePath);
        }

        return $filePath;
    }

    private function getBillingAddressData(array $row, CustomerUser $customerUser): array
    {
        return [
            'label' => $row['billingAddressLabel'],
            'country' => $row['billingAddressCountry'],
            'city' => $row['billingAddressCity'],
            'region' => $row['billingAddressRegion'],
            'street' => $row['billingAddressStreet'],
            'postalCode' => $row['billingAddressPostalCode'],
            'firstName' => $customerUser->getFirstName(),
            'lastName' => $customerUser->getLastName()
        ];
    }

    private function getShippingAddressData(array $row, CustomerUser $customerUser): array
    {
        return [
            'label' => $row['shippingAddressLabel'],
            'country' => $row['shippingAddressCountry'],
            'city' => $row['shippingAddressCity'],
            'region' => $row['shippingAddressRegion'],
            'street' => $row['shippingAddressStreet'],
            'postalCode' => $row['shippingAddressPostalCode'],
            'firstName' => $customerUser->getFirstName(),
            'lastName' => $customerUser->getLastName()
        ];
    }

    private function createOrderAddress(EntityManagerInterface $manager, array $address): OrderAddress
    {
        $orderAddress = new OrderAddress();
        $orderAddress->setLabel($address['label']);
        $orderAddress->setCountry($this->getCountryByIso2Code($manager, $address['country']));
        $orderAddress->setCity($address['city']);
        $orderAddress->setRegion($this->getRegionByIso2Code($manager, $address['region']));
        $orderAddress->setStreet($address['street']);
        $orderAddress->setPostalCode($address['postalCode']);
        $orderAddress->setFirstName($address['firstName']);
        $orderAddress->setLastName($address['lastName']);
        $orderAddress->setPhone('1234567890');

        $manager->persist($orderAddress);

        return $orderAddress;
    }

    private function addOrderLineItems(Order $order, EntityManagerInterface $manager): void
    {
        $priceTypes = [PriceTypeAwareInterface::PRICE_TYPE_UNIT, PriceTypeAwareInterface::PRICE_TYPE_BUNDLED];
        $lineItemData = $this->getLineItemData();
        foreach ($lineItemData as $row) {
            if ($order->getIdentifier() !== $row['orderIdentifier']) {
                continue;
            }

            $product = $this->getProduct($row['productSku'], $manager);
            /** @var ProductUnit $productUnit */
            $productUnit = $manager->getReference(ProductUnit::class, $row['productUnitCode']);
            $quantity = empty($row['freeFormProduct']) ? mt_rand(1, 50) : 1;

            $priceList = $this->getPriceList($order->getWebsite(), $order->getCustomer());
            $price = null !== $priceList
                ? $this->getPrice($product, $productUnit, $quantity, $order->getCurrency(), $priceList, $order)
                : Price::create(mt_rand(10, 1000), $order->getCurrency());

            $orderLineItem = new OrderLineItem();
            $orderLineItem->setProduct($product);
            $orderLineItem->setProductName($product->getName());
            $orderLineItem->setFreeFormProduct(null);
            $orderLineItem->setProductUnit($productUnit);
            $orderLineItem->setQuantity($quantity);
            $orderLineItem->setPriceType($priceTypes[array_rand($priceTypes)]);
            $orderLineItem->setPrice($price);
            $orderLineItem->setShipBy(!empty($row['shipBy']) ? new \DateTime($row['shipBy']) : null);
            $orderLineItem->setComment($row['comment']);

            $order->addLineItem($orderLineItem);
        }
    }

    private function getLineItemData(): array
    {
        if (empty($this->lineItemData)) {
            $handler = fopen(
                $this->getDataFilePath('@OroOrderBundle/Migrations/Data/Demo/ORM/data/order-line-items.csv'),
                'r'
            );
            $headers = fgetcsv($handler, 1000, ',');
            while (($data = fgetcsv($handler, 1000, ',')) !== false) {
                $this->lineItemData[] = array_combine($headers, array_values($data));
            }
            fclose($handler);
        }

        return $this->lineItemData;
    }

    private function getProduct(string $sku, ObjectManager $manager): Product
    {
        if (!isset($this->products[$sku])) {
            $this->products[$sku] = $manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }

    private function getPriceList(Website $website, ?Customer $customer): ?BasePriceList
    {
        $key = \sprintf('%d|%d', $website->getId(), $customer?->getId());
        if (!\array_key_exists($key, $this->priceLists)) {
            /** @var AbstractPriceListTreeHandler $priceListTreeHandler */
            $priceListTreeHandler = $this->container->get('oro_pricing.model.price_list_tree_handler');
            $this->priceLists[$key] = $priceListTreeHandler->getPriceList($customer, $website);
        }

        return $this->priceLists[$key];
    }

    private function getPrice(
        Product $product,
        ProductUnit $productUnit,
        int $quantity,
        string $currency,
        BasePriceList $priceList,
        Order $order
    ): Price {
        /** @var ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory */
        $productPriceCriteriaFactory = $this->container->get('oro_pricing.product_price_criteria_factory');
        /** @var ProductPriceCriteria $productPriceCriteria */
        $productPriceCriteria = $productPriceCriteriaFactory->create($product, $productUnit, $quantity, $currency);
        $identifier = $productPriceCriteria->getIdentifier();

        $priceListId = $priceList->getId();
        if (!isset($this->prices[$priceListId][$identifier])) {
            /** @var ProductPriceScopeCriteriaFactoryInterface $scopeCriteriaFactory */
            $scopeCriteriaFactory = $this->container->get('oro_pricing.model.product_price_scope_criteria_factory');
            $searchScope = $scopeCriteriaFactory->createByContext($order);
            /** @var ProductPriceProviderInterface $productPriceProvider */
            $productPriceProvider = $this->container->get('oro_pricing.provider.product_price');
            $prices = $productPriceProvider->getMatchedPrices([$productPriceCriteria], $searchScope);
            $this->prices[$priceListId][$identifier] = $prices[$identifier];
        }

        return $this->prices[$priceListId][$identifier] ?? Price::create(mt_rand(10, 1000), $currency);
    }

    private function createMultiCurrency(
        string $value,
        string $currency,
        RateConverterInterface $rateConverter
    ): MultiCurrency {
        $result = MultiCurrency::create($value, $currency);
        $result->setBaseCurrencyValue($rateConverter->getBaseCurrencyAmount($result));

        return $result;
    }

    private function getUser(string $username, ObjectManager $manager): ?User
    {
        if (!\array_key_exists($username, $this->users)) {
            $this->users[$username] = $manager->getRepository(User::class)->findOneBy(['username' => $username]);
        }

        return $this->users[$username];
    }

    private function getOrderInternalStatusByName(string $name, ObjectManager $manager): EnumOption
    {
        if (!isset($this->orderInternalStatuses[$name])) {
            $this->orderInternalStatuses[$name] = $manager->getRepository(EnumOption::class)
                ->findOneBy(['id' => ExtendHelper::buildEnumOptionId(Order::INTERNAL_STATUS_CODE, $name)]);
        }

        return $this->orderInternalStatuses[$name];
    }

    private function getOrderShippingStatusByName(?string $name, ObjectManager $manager): ?EnumOption
    {
        if (!$name) {
            return null;
        }

        if (!isset($this->orderShippingStatuses[$name])) {
            $this->orderShippingStatuses[$name] = $manager->getRepository(EnumOption::class)
                ->findOneBy(['id' => ExtendHelper::buildEnumOptionId(Order::SHIPPING_STATUS_CODE, $name)]);
        }

        return $this->orderShippingStatuses[$name];
    }

    private function getCountryByIso2Code(EntityManagerInterface $manager, string $iso2Code): ?Country
    {
        if (!\array_key_exists($iso2Code, $this->countries)) {
            $this->countries[$iso2Code] = $manager->getReference(Country::class, $iso2Code);
        }

        return $this->countries[$iso2Code];
    }

    private function getRegionByIso2Code(EntityManagerInterface $manager, string $code): ?Region
    {
        if (!\array_key_exists($code, $this->regions)) {
            $this->regions[$code] = $manager->getReference(Region::class, $code);
        }

        return $this->regions[$code];
    }

    private function getPaymentTerm(ObjectManager $manager, string $label): PaymentTerm
    {
        if (!\array_key_exists($label, $this->paymentTerms)) {
            $this->paymentTerms[$label] = $manager->getRepository(PaymentTerm::class)
                ->findOneBy(['label' => $label]);
        }

        return $this->paymentTerms[$label];
    }

    private function getWebsite(ObjectManager $manager, string $name): Website
    {
        if (!\array_key_exists($name, $this->websites)) {
            $this->websites[$name] = $manager->getRepository(Website::class)
                ->findOneBy(['name' => $name]);
        }

        return $this->websites[$name];
    }

    private function getRandomDateTime(): \DateTime
    {
        return new \DateTime(
            \sprintf('-%sday %s:%s', random_int(0, 270), random_int(0, 23), random_int(0, 59)),
            new \DateTimeZone('UTC')
        );
    }

    private function enablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        array_unshift($lifecycleCallbacks['prePersist'], 'updateTotalDiscounts');
        array_unshift($lifecycleCallbacks['prePersist'], 'prePersist');
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function disablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        $lifecycleCallbacks['prePersist'] = array_diff(
            $lifecycleCallbacks['prePersist'],
            ['prePersist', 'updateTotalDiscounts']
        );
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function toggleFeatures(?bool $enable): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_promotion.feature_enabled', $enable ?? false);
        $configManager->flush();
    }

    private function toggleListeners(?bool $enable): void
    {
        $listenerManager = $this->container->get('oro_platform.optional_listeners.manager');
        if ($enable) {
            $listenerManager->enableListener('oro_order.order.listener.orm.order_shipping_status_listener');
        } else {
            $listenerManager->disableListener('oro_order.order.listener.orm.order_shipping_status_listener');
        }
    }
}
