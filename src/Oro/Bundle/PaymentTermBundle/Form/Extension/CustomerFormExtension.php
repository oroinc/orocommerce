<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomerFormExtension extends AbstractTypeExtension
{
    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        TranslatorInterface $translator
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $customer = $builder->getData();
        if (!$customer || !$customer->getGroup()) {
            return;
        }

        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames($options['data_class']);
        if (!$associationNames) {
            return;
        }

        $paymentTermByCustomerGroup = $this->paymentTermProvider->getCustomerGroupPaymentTerm($customer->getGroup());
        if (!$paymentTermByCustomerGroup) {
            return;
        }

        foreach ($associationNames as $associationName) {
            if (!$builder->has($associationName)) {
                return;
            }

            $field = $builder->get($associationName);
            $options = $field->getOptions();

            $options['configs']['placeholder'] = $this->translator->trans(
                'oro.paymentterm.customer.customer_group_defined',
                [
                    '{{ payment_term }}' => $paymentTermByCustomerGroup->getLabel(),
                ]
            );

            $builder->add($field->getName(), $field->getType()->getName(), $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerType::NAME;
    }
}
