<?php

namespace Oro\Bundle\OrderBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethod;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Leads demo data for orders.
 */
class OrderFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    public function __construct(
        private readonly ShippingMethodProviderInterface $shippingMethodProvider
    ) {
    }

    #[\Override]
    public function getEntityClass()
    {
        return Order::class;
    }

    #[\Override]
    public function fillEntityData($key, $entity)
    {
        /** @var Order $entity */

        $organizationRepo = $this->templateManager->getEntityRepository(Organization::class);
        $userRepo = $this->templateManager->getEntityRepository(User::class);
        $customerRepo = $this->templateManager->getEntityRepository(Customer::class);
        $customerUserRepo = $this->templateManager->getEntityRepository(CustomerUser::class);

        switch ($key) {
            case 'External Order':
                $entity->setPoNumber('PO123');
                $entity->setIdentifier('EXT123');
                $entity->setOrganization($organizationRepo->getEntity('default'));
                $entity->setOwner($userRepo->getEntity('John Doo'));
                $entity->setStatus($this->createEnumOption(Order::STATUS_CODE, 'open'));
                $entity->setInternalStatus($this->createEnumOption(Order::INTERNAL_STATUS_CODE, 'open'));
                $entity->setCustomerUser($customerUserRepo->getEntity('Jerry Coleman'));
                $entity->setCustomer($customerRepo->getEntity('Company A - East Division'));
                $entity->setCurrency('USD');
                $entity->setBillingAddress($this->createExternalOrderAddress());
                $entity->setShippingAddress($this->createExternalOrderAddress());
                $entity->setShippingStatus($this->createEnumOption(Order::SHIPPING_STATUS_CODE, 'shipped'));
                $entity->setShipUntil(new \DateTime('+10 day', new \DateTimeZone('UTC')));
                $entity->setPaymentTerm7c4f1e8e($this->createPaymentTerm('net 10'));
                $entity->addLineItem($this->createExternalOrderLineItem());
                $entity->addDiscount($this->createAmountDiscount(1.2, 'Referral Bonus'));
                $entity->addDiscount($this->createPercentDiscount(0.1, 'Loyalty Program'));
                $entity->addShippingTracking($this->createShippingTracking('UPS', '1Z1234567890'));
                $this->setShippingMethod($entity);
                $entity->prePersist();

                return;
        }

        parent::fillEntityData($key, $entity);
    }

    #[\Override]
    public function getData()
    {
        return $this->getEntityData('External Order');
    }

    #[\Override]
    protected function createEntity($key)
    {
        return new Order();
    }

    private function createExternalOrderAddress(): OrderAddress
    {
        $orderAddress = new OrderAddress();
        $orderAddress->setFromExternalSource(true);
        $orderAddress->setLabel('Headquarters');
        $orderAddress->setNamePrefix('Mr.');
        $orderAddress->setFirstName('John');
        $orderAddress->setLastName('Doe');
        $orderAddress->setNameSuffix('Jr.');
        $orderAddress->setOrganization('Company A');
        $orderAddress->setCountry(new Country('US'));
        $orderAddress->setStreet('23400 Caldwell Road');
        $orderAddress->setCity('Rochester');
        $orderAddress->setRegion(new Region('US-NY'));
        $orderAddress->setPostalCode('14608');
        $orderAddress->setValidatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $orderAddress->setPhone('(+1) 212 123 4567');

        return $orderAddress;
    }

    private function createExternalOrderLineItem(): OrderLineItem
    {
        $productRepo = $this->templateManager->getEntityRepository(Product::class);

        $lineItem = new OrderLineItem();
        $lineItem->setFromExternalSource(true);
        $lineItem->setProduct($productRepo->getEntity('Product Simple'));
        $lineItem->setProductSku('sku_001');
        $lineItem->setProductName('Product Simple');
        $lineItem->setProductUnit($this->createProductUnit('item'));
        $lineItem->setQuantity(1);
        $lineItem->setPrice(Price::create(10.5, 'USD'));
        $lineItem->setShipBy(new \DateTime('+10 day', new \DateTimeZone('UTC')));

        return $lineItem;
    }

    private function createProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    private function createEnumOption(string $enumCode, string $internalId): EnumOption
    {
        return new EnumOption($enumCode, ucfirst($internalId), $internalId);
    }

    private function createPaymentTerm(string $label): PaymentTerm
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel($label);

        return $paymentTerm;
    }

    private function createAmountDiscount(float $amount, string $description): OrderDiscount
    {
        $discount = new OrderDiscount();
        $discount->setType(OrderDiscount::TYPE_AMOUNT);
        $discount->setAmount($amount);
        $discount->setDescription($description);

        return $discount;
    }

    private function createPercentDiscount(float $percent, string $description): OrderDiscount
    {
        $discount = new OrderDiscount();
        $discount->setType(OrderDiscount::TYPE_PERCENT);
        $discount->setPercent($percent);
        $discount->setDescription($description);

        return $discount;
    }

    private function createShippingTracking(string $method, string $number): OrderShippingTracking
    {
        $shippingTracking = new OrderShippingTracking();
        $shippingTracking->setMethod($method);
        $shippingTracking->setNumber($number);

        return $shippingTracking;
    }

    private function setShippingMethod(Order $order): void
    {
        /** @var ShippingMethodInterface|null $foundShippingMethod */
        $foundShippingMethod = null;
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod instanceof FlatRateMethod) {
                $foundShippingMethod = $shippingMethod;
                break;
            }
        }
        if (null !== $foundShippingMethod) {
            $order->setShippingMethod($foundShippingMethod->getIdentifier());
            $shippingMethodTypes = $foundShippingMethod->getTypes();
            if ($shippingMethodTypes) {
                $shippingMethodType = reset($shippingMethodTypes);
                $order->setShippingMethodType($shippingMethodType->getIdentifier());
            }
        }
    }
}
