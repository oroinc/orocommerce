<?php

namespace Oro\Bundle\InvoiceBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use Oro\Bundle\AccountBundle\Form\Type\AccountSelectType;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;

class InvoiceType extends AbstractType
{
    const NAME = 'oro_invoice_type';

    /**
     * @var string
     */
    protected $dataClass;

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
        /** @var Invoice $invoice */
        $invoice = $options['data'];
        $builder
            ->add(
                'owner',
                'oro_user_select',
                [
                    'label' => 'oro.invoice.owner.label',
                    'required' => true,
                ]
            )
            ->add(
                'accountUser',
                AccountUserSelectType::NAME,
                [
                    'label' => 'oro.invoice.account_user.label',
                    'required' => false,
                ]
            )
            ->add(
                'account',
                AccountSelectType::NAME,
                [
                    'label' => 'oro.invoice.account.label',
                    'required' => true,
                ]
            )
            ->add(
                'invoiceDate',
                OroDateType::NAME,
                [
                    'label' => 'oro.invoice.invoice_date.label',
                    'required' => true,
                ]
            )
            ->add(
                'paymentDueDate',
                OroDateType::NAME,
                [
                    'label' => 'oro.invoice.payment_due_date.label',
                    'required' => true,
                ]
            )
            ->add('poNumber', 'text', [
                'required' => false
            ])
            ->add(
                'currency',
                CurrencySelectionType::NAME,
                [
                    'required' => true,
                    'label' => 'oro.invoice.currency.label',
                ]
            )
            ->add(
                'lineItems',
                InvoiceLineItemsCollectionType::NAME,
                [
                    'add_label' => 'oro.invoice.invoicelineitem.add_label',
                    'cascade_validation' => true,
                    'options' => ['currency' => $invoice->getCurrency()],
                ]
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
                'intention' => 'invoice',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ]
        );
    }

    /**
     * {@inheritdoc}
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
}
