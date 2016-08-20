<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;

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

    /** @var SubtotalSubscriber */
    protected $subtotalSubscriber;

    /** @var PriceListRequestHandler */
    protected $priceListRequestHandler;

    /**
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param SecurityFacade $securityFacade
     * @param PaymentTermProvider $paymentTermProvider
     * @param ProductPriceProvider $productPriceProvider
     * @param OrderCurrencyHandler $orderCurrencyHandler
     * @param SubtotalSubscriber $subtotalSubscriber
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        SecurityFacade $securityFacade,
        PaymentTermProvider $paymentTermProvider,
        ProductPriceProvider $productPriceProvider,
        OrderCurrencyHandler $orderCurrencyHandler,
        SubtotalSubscriber $subtotalSubscriber,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->securityFacade = $securityFacade;
        $this->paymentTermProvider = $paymentTermProvider;
        $this->productPriceProvider = $productPriceProvider;
        $this->orderCurrencyHandler = $orderCurrencyHandler;
        $this->subtotalSubscriber = $subtotalSubscriber;
        $this->priceListRequestHandler = $priceListRequestHandler;
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
            ->add('poNumber', 'text', ['required' => false, 'label' => 'oro.order.po_number.label'])
            ->add('shipUntil', OroDateType::NAME, ['required' => false, 'label' => 'oro.order.ship_until.label'])
            ->add(
                'customerNotes',
                'textarea',
                ['required' => false, 'label' => 'oro.order.customer_notes.frontend.label']
            )
            ->add(
                'lineItems',
                OrderLineItemsCollectionType::NAME,
                [
                    'type' => FrontendOrderLineItemType::NAME,
                    'add_label' => 'oro.order.orderlineitem.add_label',
                    'cascade_validation' => true,
                    'options' => ['currency' => $order->getCurrency()]
                ]
            )
            ->add('sourceEntityClass', 'hidden')
            ->add('sourceEntityId', 'hidden');

        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_BILLING)) {
            $builder->add(
                'billingAddress',
                OrderAddressType::NAME,
                [
                    'label' => 'oro.order.billing_address.label',
                    'object' => $options['data'],
                    'required' => false,
                    'addressType' => AddressType::TYPE_BILLING,
                ]
            );
        }

        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_SHIPPING)) {
            /** @var Order $object */
            $object = $options['data'];
            $isEditEnabled = true;
            if ($object->getShippingAddress()) {
                $isEditEnabled = !$object->getShippingAddress()->isFromExternalSource();
            }
            $builder->add(
                'shippingAddress',
                OrderAddressType::NAME,
                [
                    'label' => 'oro.order.shipping_address.label',
                    'object' => $object,
                    'required' => false,
                    'addressType' => AddressType::TYPE_SHIPPING,
                    'isEditEnabled' => $isEditEnabled
                ]
            );
        }

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'updateLineItemPrices']);
        $builder->addEventSubscriber($this->subtotalSubscriber);
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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
            $productsPriceCriteria = [];
            /** @var OrderLineItem[] $lineItemsWithIdentifier */
            $lineItemsWithIdentifier = [];

            foreach ($order->getLineItems() as $lineItem) {
                if (!$this->isValidForPriceUpdate($lineItem)) {
                    continue;
                }

                $productPriceCriteria = new ProductPriceCriteria(
                    $lineItem->getProduct(),
                    $lineItem->getProductUnit(),
                    $lineItem->getQuantity(),
                    $order->getCurrency()
                );

                $productsPriceCriteria[] = $productPriceCriteria;
                $lineItemsWithIdentifier[$productPriceCriteria->getIdentifier()] = $lineItem;
            }

            $this->fillLineItemsPrice($productsPriceCriteria, $lineItemsWithIdentifier);
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
     * @param ProductPriceCriteria[] $productsPriceCriteria
     * @param OrderLineItem[] $lineItemsWithIdentifier
     */
    protected function fillLineItemsPrice(array $productsPriceCriteria, array $lineItemsWithIdentifier)
    {
        $prices = $this->productPriceProvider->getMatchedPrices(
            $productsPriceCriteria,
            $this->priceListRequestHandler->getPriceListByAccount()
        );

        foreach ($lineItemsWithIdentifier as $identifier => $lineItem) {
            if (array_key_exists($identifier, $prices) && $prices[$identifier] instanceof Price) {
                $lineItem->setPrice($prices[$identifier]);
            }
        }
    }
}
