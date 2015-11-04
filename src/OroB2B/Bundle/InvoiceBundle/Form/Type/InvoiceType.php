<?php

namespace OroB2B\Bundle\InvoiceBundle\Form\Type;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;

/**
 * {@inheritdoc}
 */
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
            ->add('owner', 'oro_user_select', [
                'label'     => 'orob2b.invoice.owner.label',
                'required'  => true,
            ])
            ->add('accountUser', AccountUserSelectType::NAME, [
                'label'     => 'orob2b.invoice.account_user.label',
                'required'  => true,
            ])
            ->add('account', AccountSelectType::NAME, [
                'label'     => 'orob2b.invoice.account.label',
                'required'  => false,
            ])

            ->add('invoiceDate', OroDateTimeType::NAME, [
                'label'     => 'orob2b.invoice.invoice_date.label',
                'required'  => true,
            ])
            ->add('paymentDueDate', OroDateTimeType::NAME, [
                'label'     => 'orob2b.invoice.invoice_date.label',
                'required'  => true,
            ])
            ->add('poNumber', 'text')
            ->add(
                'lineItems',
                InvoiceLineItemsCollectionType::NAME,
                [
                    'add_label' => 'orob2b.invoice.invoicelineitem.add_label',
                    'cascade_validation' => true,
                    'options' => ['currency' => $invoice->getCurrency()]
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => $this->dataClass,
            'intention'     => 'invoice',
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
