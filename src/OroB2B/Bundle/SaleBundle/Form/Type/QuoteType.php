<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;

use OroB2B\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserMultiSelectType;

class QuoteType extends AbstractType
{
    const NAME = 'orob2b_sale_quote';

    /**
     * @var QuoteAddressSecurityProvider
     */
    protected $quoteAddressSecurityProvider;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param QuoteAddressSecurityProvider $quoteAddressSecurityProvider
     */
    public function __construct(QuoteAddressSecurityProvider $quoteAddressSecurityProvider)
    {
        $this->quoteAddressSecurityProvider = $quoteAddressSecurityProvider;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $quote = $options['data'];

        $builder
            ->add('qid', 'hidden')
            ->add('owner', 'oro_user_select', [
                'label'     => 'orob2b.sale.quote.owner.label',
                'required'  => true
            ])
            ->add('accountUser', AccountUserSelectType::NAME, [
                'label'     => 'orob2b.sale.quote.account_user.label',
                'required'  => false
            ])
            ->add('account', AccountSelectType::NAME, [
                'label'     => 'orob2b.sale.quote.account.label',
                'required'  => false
            ])
            ->add('validUntil', OroDateTimeType::NAME, [
                'label'     => 'orob2b.sale.quote.valid_until.label',
                'required'  => false
            ])
            ->add('locked', 'checkbox', [
                'label' => 'orob2b.sale.quote.locked.label',
                'required'  => false
            ])
            ->add('poNumber', 'text', [
                'required' => false,
                'label' => 'orob2b.sale.quote.po_number.label'
            ])
            ->add('shipUntil', OroDateType::NAME, [
                'required' => false,
                'label' => 'orob2b.sale.quote.ship_until.label'
            ])
            ->add(
                'quoteProducts',
                QuoteProductCollectionType::NAME,
                [
                    'add_label' => 'orob2b.sale.quoteproduct.add_label',
                    'options' => [
                        'compact_units' => true,
                    ]
                ]
            )
            ->add('assignedUsers', UserMultiSelectType::NAME, [
                'label' => 'orob2b.sale.quote.assigned_users.label',
            ])
            ->add('assignedAccountUsers', AccountUserMultiSelectType::NAME, [
                'label' => 'orob2b.sale.quote.assigned_account_users.label',
            ])
            ->add(
                'shippingEstimate',
                PriceType::NAME,
                [
                    'currency_empty_value' => null,
                    'error_bubbling' => false,
                    'required' => false,
                    'label' => 'orob2b.sale.quote.shipping_estimate.label',
                    'validation_groups' => ['Optional']
                ]
            )
        ;

        if ($this->quoteAddressSecurityProvider->isAddressGranted($quote, AddressType::TYPE_SHIPPING)) {
            $builder
                ->add(
                    'shippingAddress',
                    QuoteAddressType::NAME,
                    [
                        'label' => 'orob2b.sale.quote.shipping_address.label',
                        'quote' => $options['data'],
                        'required' => false,
                        'addressType' => AddressType::TYPE_SHIPPING,
                    ]
                );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => $this->dataClass,
            'intention'     => 'sale_quote',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
