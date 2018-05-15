<?php

namespace Oro\Bundle\InvoiceBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                UserSelectType::class,
                [
                    'label' => 'oro.invoice.owner.label',
                    'required' => true,
                ]
            )
            ->add(
                'customerUser',
                CustomerUserSelectType::class,
                [
                    'label' => 'oro.invoice.customer_user.label',
                    'required' => false,
                ]
            )
            ->add(
                'customer',
                CustomerSelectType::class,
                [
                    'label' => 'oro.invoice.customer.label',
                    'required' => true,
                ]
            )
            ->add(
                'invoiceDate',
                OroDateType::class,
                [
                    'label' => 'oro.invoice.invoice_date.label',
                    'required' => true,
                ]
            )
            ->add(
                'paymentDueDate',
                OroDateType::class,
                [
                    'label' => 'oro.invoice.payment_due_date.label',
                    'required' => true,
                ]
            )
            ->add('poNumber', TextType::class, [
                'required' => false
            ])
            ->add(
                'currency',
                CurrencySelectionType::class,
                [
                    'required' => true,
                    'label' => 'oro.invoice.currency.label',
                ]
            )
            ->add(
                'lineItems',
                InvoiceLineItemsCollectionType::class,
                [
                    'add_label' => 'oro.invoice.invoicelineitem.add_label',
                    'entry_options' => ['currency' => $invoice->getCurrency()],
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
                'csrf_token_id' => 'invoice',
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
