<?php

namespace Oro\Bundle\InvoiceBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
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
                'customerUser',
                CustomerUserSelectType::NAME,
                [
                    'label' => 'oro.invoice.customer_user.label',
                    'required' => false,
                ]
            )
            ->add(
                'customer',
                CustomerSelectType::NAME,
                [
                    'label' => 'oro.invoice.customer.label',
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
