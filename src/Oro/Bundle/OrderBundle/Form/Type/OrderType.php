<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\PossibleShippingMethodEventListener;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Represents order form type
 */
class OrderType extends AbstractType
{
    const NAME = 'oro_order_type';
    const DISCOUNTS_FIELD_NAME = 'discounts';

    /** @var string */
    protected $dataClass;

    /** @var OrderAddressSecurityProvider */
    protected $orderAddressSecurityProvider;

    /** @var OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    /** @var SubtotalSubscriber */
    protected $subtotalSubscriber;

    public function __construct(
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderCurrencyHandler $orderCurrencyHandler,
        SubtotalSubscriber $subtotalSubscriber
    ) {
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->orderCurrencyHandler = $orderCurrencyHandler;
        $this->subtotalSubscriber = $subtotalSubscriber;
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Order $order */
        $order = $options['data'];
        $this->orderCurrencyHandler->setOrderCurrency($order);

        $builder
            ->add('customer', CustomerSelectType::class, ['label' => 'oro.order.customer.label', 'required' => true])
            ->add(
                'customerUser',
                CustomerUserSelectType::class,
                [
                    'label' => 'oro.order.customer_user.label',
                    'required' => false,
                ]
            )
            ->add('poNumber', TextType::class, ['required' => false, 'label' => 'oro.order.po_number.label'])
            ->add('shipUntil', OroDateType::class, ['required' => false, 'label' => 'oro.order.ship_until.label'])
            ->add(
                'customerNotes',
                TextareaType::class,
                ['required' => false, 'label' => 'oro.order.customer_notes.label']
            )
            ->add('currency', CurrencySelectionType::class, [
                'label' => 'oro.order.currency.label',
                'full_currency_name' => true,
            ])
            ->add(
                'lineItems',
                OrderLineItemsCollectionType::class,
                [
                    'add_label' => 'oro.order.orderlineitem.add_label',
                    'entry_options' => ['currency' => $order->getCurrency()]
                ]
            )
            ->add(
                self::DISCOUNTS_FIELD_NAME,
                OrderDiscountCollectionTableType::class,
                ['order' => $order]
            )
            ->add(
                'discountsSum',
                HiddenType::class,
                [
                    'mapped' => false,
                    //range should be used, because this type also is implemented with JS
                    'constraints' => [new Range(
                        [
                            'min' => PHP_INT_MAX * (-1), //use some big negative number
                            'max' => $order->getSubtotal(),
                            'notInRangeMessage' => 'oro.order.discounts.sum.error.not_in_range.label'
                        ]
                    )],
                    'data' => $order->getTotalDiscounts() ? $order->getTotalDiscounts()->getValue() : 0
                ]
            )
            ->add('sourceEntityClass', HiddenType::class)
            ->add('sourceEntityId', HiddenType::class)
            ->add('sourceEntityIdentifier', HiddenType::class);
        $this->addShippingFields($builder, $order);
        $this->addAddresses($builder, $order);
        $this->addBillingAddress($builder, $order, $options);
        $this->addShippingAddress($builder, $order, $options);

        $builder->addEventSubscriber($this->subtotalSubscriber);
    }

    /**
     * @param FormBuilderInterface|FormInterface $form
     * @param Order $order
     * @throws \InvalidArgumentException
     */
    protected function addAddresses($form, Order $order)
    {
        if (!$form instanceof FormInterface && !$form instanceof FormBuilderInterface) {
            throw new \InvalidArgumentException('Invalid form');
        }

        foreach ([AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING] as $type) {
            if ($this->orderAddressSecurityProvider->isAddressGranted($order, $type)) {
                $options = [
                    'label' => sprintf('oro.order.%s_address.label', $type),
                    'object' => $order,
                    'required' => false,
                    'addressType' => $type,
                ];

                $form->add(sprintf('%sAddress', $type), OrderAddressType::class, $options);
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'csrf_token_id' => 'order'
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
     * @param FormBuilderInterface $builder
     * @param Order $order
     * @param array $options
     */
    protected function addBillingAddress(FormBuilderInterface $builder, Order $order, $options)
    {
        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_BILLING)) {
            $builder
                ->add(
                    'billingAddress',
                    OrderAddressType::class,
                    [
                        'label' => 'oro.order.billing_address.label',
                        'object' => $options['data'],
                        'required' => false,
                        'addressType' => AddressType::TYPE_BILLING,
                    ]
                );
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param Order $order
     * @param array $options
     */
    protected function addShippingAddress(FormBuilderInterface $builder, Order $order, $options)
    {
        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_SHIPPING)) {
            $builder
                ->add(
                    'shippingAddress',
                    OrderAddressType::class,
                    [
                        'label' => 'oro.order.shipping_address.label',
                        'object' => $options['data'],
                        'required' => false,
                        'addressType' => AddressType::TYPE_SHIPPING,
                    ]
                );
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param Order $order
     * @return $this
     */
    protected function addShippingFields(FormBuilderInterface $builder, Order $order)
    {
        $builder
            ->add(PossibleShippingMethodEventListener::CALCULATE_SHIPPING_KEY, HiddenType::class, [
                'mapped' => false
            ])
            ->add('shippingMethod', HiddenType::class)
            ->add('shippingMethodType', HiddenType::class)
            ->add('estimatedShippingCostAmount', HiddenType::class)
            ->add('overriddenShippingCostAmount', PriceType::class, [
                'required' => false,
                'validation_groups' => ['Optional'],
                'hide_currency' => true,
            ])
            ->get('overriddenShippingCostAmount')->addModelTransformer(new CallbackTransformer(
                function ($amount) use ($order) {
                    return $amount ? Price::create($amount, $order->getCurrency()) : null;
                },
                function ($price) {
                    return $price instanceof Price ? $price->getValue() : $price;
                }
            ))
        ;

        return $this;
    }
}
