<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class OrderType extends AbstractType
{
    const NAME = 'orob2b_order_type';

    /** @var  string */
    protected $dataClass;

    /** @var OrderAddressSecurityProvider */
    protected $orderAddressSecurityProvider;

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param PaymentTermProvider $paymentTermProvider
     */
    public function __construct(
        SecurityFacade $securityFacade,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        PaymentTermProvider $paymentTermProvider
    ) {
        $this->securityFacade = $securityFacade;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->paymentTermProvider = $paymentTermProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Order $order */
        $order = $options['data'];

        $builder
            ->add('account', AccountSelectType::NAME, ['label' => 'orob2b.order.account.label', 'required' => true])
            ->add(
                'accountUser',
                AccountUserSelectType::NAME,
                [
                    'label' => 'orob2b.order.account_user.label',
                    'required' => false,
                    //TODO remove this after related entity selection fix
                    'attr' => [
                        'class' => 'order-order-accountuser-select'
                    ]
                ]
            )
            ->add('poNumber', 'text', ['required' => false, 'label' => 'orob2b.order.po_number.label'])
            ->add('shipUntil', OroDateType::NAME, ['required' => false, 'label' => 'orob2b.order.ship_until.label'])
            ->add('customerNotes', 'textarea', ['required' => false, 'label' => 'orob2b.order.customer_notes.label'])
            ->add(
                'priceList',
                PriceListSelectType::NAME,
                [
                    'required' => false,
                    'label' => 'orob2b.order.price_list.label',
                ]
            )
            ->add(
                'lineItems',
                OrderLineItemsCollectionType::NAME,
                [
                    'add_label' => 'orob2b.order.orderlineitem.add_label',
                    'cascade_validation' => true,
                ]
            );

        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_BILLING)) {
            $builder
                ->add(
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
            $builder
                ->add(
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

        if ($this->isOverridePaymentTermGranted()) {
            $builder
                ->add(
                    'paymentTerm',
                    PaymentTermSelectType::NAME,
                    [
                        'label' => 'orob2b.order.payment_term.label',
                        'required' => false,
                        'attr' => [
                            'data-account-payment-term' => $this->getAccountPaymentTermId($order),
                            'data-account-group-payment-term' => $this->getAccountGroupPaymentTermId($order),
                        ],
                    ]
                );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'order',
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
     * @return bool
     */
    protected function isOverridePaymentTermGranted()
    {
        return $this->securityFacade->isGranted('orob2b_order_payment_term_account_can_override');
    }

    /**
     * @param Order $order
     * @return int|null
     */
    protected function getAccountPaymentTermId(Order $order)
    {
        $account = $order->getAccount();
        if (!$account) {
            return null;
        }

        $paymentTerm = $this->paymentTermProvider->getAccountPaymentTerm($account);

        return $paymentTerm ? $paymentTerm->getId() : null;
    }

    /**
     * @param Order $order
     * @return int|null
     */
    protected function getAccountGroupPaymentTermId(Order $order)
    {
        $account = $order->getAccount();
        if (!$account || !$account->getGroup()) {
            return null;
        }

        $paymentTerm = $this->paymentTermProvider->getAccountGroupPaymentTerm($account->getGroup());

        return $paymentTerm ? $paymentTerm->getId() : null;
    }
}
