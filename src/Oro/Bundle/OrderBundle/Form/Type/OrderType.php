<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumIdChoiceType;
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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * The form type for Order entity.
 */
class OrderType extends AbstractType
{
    public const NAME = 'oro_order_type';
    public const DISCOUNTS_FIELD_NAME = 'discounts';

    public function __construct(
        private OrderAddressSecurityProvider $orderAddressSecurityProvider,
        private OrderCurrencyHandler $orderCurrencyHandler,
        private SubtotalSubscriber $subtotalSubscriber
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $order = $options['data'];
        $this->orderCurrencyHandler->setOrderCurrency($order);

        $builder
            ->add('customer', CustomerSelectType::class, ['label' => 'oro.order.customer.label', 'required' => true])
            ->add(
                'customerUser',
                CustomerUserSelectType::class,
                ['required' => false, 'label' => 'oro.order.customer_user.label']
            )
            ->add('poNumber', TextType::class, ['required' => false, 'label' => 'oro.order.po_number.label'])
            ->add('shipUntil', OroDateType::class, ['required' => false, 'label' => 'oro.order.ship_until.label'])
            ->add(
                'shippingStatus',
                EnumIdChoiceType::class,
                [
                    'required' => false,
                    'label' => 'oro.order.shipping_status.label',
                    'enum_code' => Order::SHIPPING_STATUS_CODE,
                    'multiple' => false
                ]
            )
            ->add(
                'customerNotes',
                TextareaType::class,
                ['required' => false, 'label' => 'oro.order.customer_notes.label']
            )
            ->add('currency', CurrencySelectionType::class, [
                'label' => 'oro.order.currency.label',
                'full_currency_name' => true,
            ])
            ->add('sourceEntityClass', HiddenType::class)
            ->add('sourceEntityId', HiddenType::class)
            ->add('sourceEntityIdentifier', HiddenType::class);

        $this->addAddresses($builder, $order);
        $this->addBillingAddress($builder, $order, $options);

        $this->addPreSubmitEventListener($builder);
        $builder->addEventSubscriber($this->subtotalSubscriber);

        if (!$order->getSubOrders()->count()) {
            $builder
                ->add(
                    'lineItems',
                    OrderLineItemsCollectionType::class,
                    [
                        'add_label' => 'oro.order.orderlineitem.add_label',
                        'entry_options' => ['currency' => $order->getCurrency()],
                        'allow_add' => true,
                        'allow_delete' => true,
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
                        'constraints' => [new Range([
                            'min' => PHP_INT_MAX * (-1), //use some big negative number
                            'max' => $order->getSubtotal(),
                            'notInRangeMessage' => 'oro.order.discounts.sum.error.not_in_range.label'
                        ])],
                        'data' => $order->getTotalDiscounts() ? $order->getTotalDiscounts()->getValue() : 0
                    ]
                );
            $this->addShippingFields($builder, $order);
            $this->addShippingAddress($builder, $order, $options);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'csrf_token_id' => 'order'
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    private function addAddresses(FormBuilderInterface $builder, Order $order): void
    {
        $addressTypes = [AddressType::TYPE_BILLING];
        if (!$order->getSubOrders()->count()) {
            $addressTypes[] = AddressType::TYPE_SHIPPING;
        }
        foreach ($addressTypes as $type) {
            if ($this->orderAddressSecurityProvider->isAddressGranted($order, $type)) {
                $options = [
                    'label' => sprintf('oro.order.%s_address.label', $type),
                    'object' => $order,
                    'required' => false,
                    'addressType' => $type,
                ];
                $builder->add(sprintf('%sAddress', $type), OrderAddressType::class, $options);
            }
        }
    }

    private function addBillingAddress(FormBuilderInterface $builder, Order $order, array $options): void
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

    private function addShippingAddress(FormBuilderInterface $builder, Order $order, array $options): void
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

    private function addShippingFields(FormBuilderInterface $builder, Order $order): void
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
            ));
    }

    private function addPreSubmitEventListener(FormBuilderInterface $builder): void
    {
        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                if (!$this->orderAddressSecurityProvider->isManualEditGranted(AddressType::TYPE_BILLING)) {
                    $event->getForm()->remove('billingAddress');
                }
                if (!$this->orderAddressSecurityProvider->isManualEditGranted(AddressType::TYPE_SHIPPING)) {
                    $event->getForm()->remove('shippingAddress');
                }
            });
    }
}
