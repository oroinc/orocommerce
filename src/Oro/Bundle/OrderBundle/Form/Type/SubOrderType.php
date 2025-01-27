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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Represents sub order form type
 */
class SubOrderType extends AbstractType
{
    private const NAME = 'oro_suborder_type';
    private const DISCOUNTS_FIELD_NAME = 'discounts';

    private OrderAddressSecurityProvider $orderAddressSecurityProvider;
    protected OrderCurrencyHandler $orderCurrencyHandler;
    protected SubtotalSubscriber $subtotalSubscriber;

    public function __construct(
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderCurrencyHandler $orderCurrencyHandler,
        SubtotalSubscriber $subtotalSubscriber
    ) {
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->orderCurrencyHandler = $orderCurrencyHandler;
        $this->subtotalSubscriber = $subtotalSubscriber;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $order = $options['data'];
        $this->orderCurrencyHandler->setOrderCurrency($order);

        $builder
            ->add(
                'customer',
                CustomerSelectType::class,
                [
                    'label' => 'oro.order.customer.label',
                    'required' => false,
                    'disabled' => true
                ]
            )
            ->add(
                'customerUser',
                CustomerUserSelectType::class,
                [
                    'label' => 'oro.order.customer_user.label',
                    'required' => false,
                    'disabled' => true
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
            ->add('sourceEntityIdentifier', HiddenType::class)
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                if (!$this->orderAddressSecurityProvider->isManualEditGranted(AddressType::TYPE_SHIPPING)) {
                    $event->getForm()->remove('shippingAddress');
                }
            });
        $this->addShippingFields($builder, $order);
        $this->addAddresses($builder, $order);
        $this->addShippingAddress($builder, $order, $options);

        $builder->addEventSubscriber($this->subtotalSubscriber);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Order::class,
                'csrf_token_id' => 'order'
            ]
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    protected function addAddresses(FormBuilderInterface|FormInterface $form, Order $order): void
    {
        if (!$form instanceof FormInterface && !$form instanceof FormBuilderInterface) {
            throw new \InvalidArgumentException('Invalid form');
        }

        foreach ([AddressType::TYPE_SHIPPING] as $type) {
            if ($this->orderAddressSecurityProvider->isAddressGranted($order, $type)) {
                $options = [
                    'label' => sprintf('oro.order.%s_address.label', $type),
                    'order' => $order,
                    'required' => false,
                    'address_type' => $type,
                ];

                $form->add(sprintf('%sAddress', $type), OrderAddressType::class, $options);
            }
        }
    }

    protected function addShippingAddress(FormBuilderInterface $builder, Order $order, array $options): void
    {
        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_SHIPPING)) {
            $builder
                ->add(
                    'shippingAddress',
                    OrderAddressType::class,
                    [
                        'label' => 'oro.order.shipping_address.label',
                        'order' => $options['data'],
                        'required' => false,
                        'address_type' => AddressType::TYPE_SHIPPING,
                    ]
                );
        }
    }

    protected function addShippingFields(FormBuilderInterface $builder, Order $order): self
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

        return $this;
    }
}
