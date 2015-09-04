<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;

class FrontendOrderType extends AbstractType
{
    const NAME = 'orob2b_order_frontend_type';

    /** @var string */
    protected $dataClass;

    /** @var OrderAddressSecurityProvider */
    protected $orderAddressSecurityProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var ProductPriceProvider */
    protected $productPriceProvider;

    /** @var OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    /**
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param SecurityFacade $securityFacade
     * @param PaymentTermProvider $paymentTermProvider
     * @param ProductPriceProvider $productPriceProvider
     * @param OrderCurrencyHandler $orderCurrencyHandler
     */
    public function __construct(
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        SecurityFacade $securityFacade,
        PaymentTermProvider $paymentTermProvider,
        ProductPriceProvider $productPriceProvider,
        OrderCurrencyHandler $orderCurrencyHandler
    ) {
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->securityFacade = $securityFacade;
        $this->paymentTermProvider = $paymentTermProvider;
        $this->productPriceProvider = $productPriceProvider;
        $this->orderCurrencyHandler = $orderCurrencyHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Order $order */
        $order = $options['data'];
        $this->orderCurrencyHandler->setOrderCurrency($order);

        $builder
            ->add('poNumber', 'text', ['required' => false, 'label' => 'orob2b.order.po_number.label'])
            ->add('shipUntil', OroDateType::NAME, ['required' => false, 'label' => 'orob2b.order.ship_until.label'])
            ->add(
                'customerNotes',
                'textarea',
                ['required' => false, 'label' => 'orob2b.order.customer_notes.frontend.label']
            )
            ->add(
                'lineItems',
                OrderLineItemsCollectionType::NAME,
                [
                    'type' => FrontendOrderLineItemType::NAME,
                    'add_label' => 'orob2b.order.orderlineitem.add_label',
                    'cascade_validation' => true,
                    'options' => ['currency' => $order->getCurrency()]
                ]
            );

        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_BILLING)) {
            $builder->add(
                'billingAddress',
                OrderAddressType::NAME,
                [
                    'label' => 'orob2b.order.billing_address.label',
                    'order' => $options['data'],
                    'required' => false,
                    'addressType' => AddressType::TYPE_BILLING,
                ]
            );
        }

        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_SHIPPING)) {
            $builder->add(
                'shippingAddress',
                OrderAddressType::NAME,
                [
                    'label' => 'orob2b.order.shipping_address.label',
                    'order' => $options['data'],
                    'required' => false,
                    'addressType' => AddressType::TYPE_SHIPPING,
                ]
            );
        }

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'updateLineItemPrices']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'validation_groups' => ['Default', 'Frontend']
            ]
        );
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormEvent $event
     */
    public function updateLineItemPrices(FormEvent $event)
    {
        /** @var Order $order */
        $order = $event->getData();
        if ($order && $order->getLineItems()) {
            $productUnitQuantities = [];
            /** @var OrderLineItem[] $lineItemsWithIdentifier */
            $lineItemsWithIdentifier = [];

            foreach ($order->getLineItems() as $lineItem) {
                if (!$this->isValidForPriceUpdate($lineItem)) {
                    continue;
                }

                $productUnitQuantity = new ProductUnitQuantity(
                    $lineItem->getProduct(),
                    $lineItem->getProductUnit(),
                    $lineItem->getQuantity()
                );

                $productUnitQuantities[] = $productUnitQuantity;
                $lineItemsWithIdentifier[$productUnitQuantity->getIdentifier()] = $lineItem;
            }

            $this->fillLineItemsPrice($order->getCurrency(), $productUnitQuantities, $lineItemsWithIdentifier);
        }
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     */
    protected function isValidForPriceUpdate(OrderLineItem $lineItem)
    {
        return $lineItem->getProduct()
            && $lineItem->getProductUnit()
            && $lineItem->getQuantity()
            && !$lineItem->getPrice()
            && !$lineItem->isFromExternalSource()
            && $lineItem->isRequirePriceRecalculation();
    }

    /**
     * @param string $currency
     * @param ProductUnitQuantity[] $productUnitQuantities
     * @param OrderLineItem[] $lineItemsWithIdentifier
     */
    protected function fillLineItemsPrice($currency, array $productUnitQuantities, array $lineItemsWithIdentifier)
    {
        $prices = $this->productPriceProvider->getMatchedPrices($productUnitQuantities, $currency);

        foreach ($lineItemsWithIdentifier as $identifier => $lineItem) {
            if (array_key_exists($identifier, $prices) && $prices[$identifier] instanceof Price) {
                $lineItem->setPrice($prices[$identifier]);
            }
        }
    }
}
