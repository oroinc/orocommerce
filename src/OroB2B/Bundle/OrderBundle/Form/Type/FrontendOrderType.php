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

    /**
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param SecurityFacade $securityFacade
     * @param PaymentTermProvider $paymentTermProvider
     * @param ProductPriceProvider $productPriceProvider
     */
    public function __construct(
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        SecurityFacade $securityFacade,
        PaymentTermProvider $paymentTermProvider,
        ProductPriceProvider $productPriceProvider
    ) {
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->securityFacade = $securityFacade;
        $this->paymentTermProvider = $paymentTermProvider;
        $this->productPriceProvider = $productPriceProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Order $order */
        $order = $options['data'];

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

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($options) {
                /** @var Order $order */
                $order = $event->getData();
                if ($order && $order->getLineItems()) {
                    $productUnitQuantities = [];

                    foreach ($order->getLineItems() as $lineItem) {
                        $productUnitQuantityCollection[] = new ProductUnitQuantity(
                            $lineItem->getProduct(),
                            $lineItem->getProductUnit(),
                            $lineItem->getQuantity()
                        );
                    }

                    $prices = $this->productPriceProvider->matchPrices($productUnitQuantities, $order->getCurrency());

                    foreach ($order->getLineItems() as $lineItem) {
                        $key = sprintf(
                            '%s-%s-%s',
                            $lineItem->getProduct()->getId(),
                            $lineItem->getProductUnit()->getCode(),
                            $lineItem->getQuantity()
                        );

                        if (array_key_exists($key, $prices) && $prices[$key] instanceof Price) {
                            $lineItem->setPrice($prices[$key]);
                        }
                    }
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
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
}
