<?php

namespace OroB2B\Bundle\InvoiceBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

class InvoiceType extends AbstractType
{
    const NAME = 'orob2b_invoice_type';

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
                    'label' => 'orob2b.invoice.owner.label',
                    'required' => true,
                ]
            )
            ->add(
                'accountUser',
                AccountUserSelectType::NAME,
                [
                    'label' => 'orob2b.invoice.account_user.label',
                    'required' => false,
                ]
            )
            ->add(
                'account',
                AccountSelectType::NAME,
                [
                    'label' => 'orob2b.invoice.account.label',
                    'required' => true,
                ]
            )
            ->add(
                'invoiceDate',
                OroDateType::NAME,
                [
                    'label' => 'orob2b.invoice.invoice_date.label',
                    'required' => true,
                ]
            )
            ->add(
                'paymentDueDate',
                OroDateType::NAME,
                [
                    'label' => 'orob2b.invoice.payment_due_date.label',
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
                    'label' => 'orob2b.invoice.currency.label',
                ]
            )
            ->add(
                'lineItems',
                InvoiceLineItemsCollectionType::NAME,
                [
                    'add_label' => 'orob2b.invoice.invoicelineitem.add_label',
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
        return self::NAME;
    }
}
