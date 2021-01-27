<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\OrderMapper;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadOrderData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const PROMOTION_ORDER_1 = 'promo_order_1';

    /** {@inheritdoc} */
    public function getDependencies()
    {
        return [LoadCheckoutData::class];
    }

    /**
     * @var array
     */
    protected $orderData = [
        self::PROMOTION_ORDER_1 => [
            'checkoutReference' => LoadCheckoutData::PROMOTION_CHECKOUT_1,
            'shippingMethod' => 'flat-rate',
            'shippingMethodType' => 'air'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->orderData as $reference => $data) {
            /** @var Checkout $checkout */
            $checkout = $this->getReference($data['checkoutReference']);

            $lineItems = [];
            /** @var LineItem $lineItem */
            foreach ($checkout->getLineItems() as $lineItem) {
                $orderLineItem = new OrderLineItem();
                $orderLineItem
                    ->setCurrency('USD')
                    ->setPrice(Price::create(10, 'USD'))
                    ->setQuantity($lineItem->getQuantity())
                    ->setProductUnitCode($lineItem->getProductUnitCode())
                    ->setProductUnit($lineItem->getProductUnit())
                    ->setProduct($lineItem->getProduct());

                $lineItems[] = $orderLineItem;
            }

            $entity = $this->getMapper()->map($checkout, ['lineItems' => $lineItems]);
            $entity->setShippingMethod('flat-rate');
            $entity->setShippingMethodType('air');

            $this->setReference($reference, $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @return OrderMapper
     */
    private function getMapper()
    {
        return $this->container->get('oro_checkout.alias.mapper.order_mapper');
    }
}
