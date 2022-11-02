<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This form extension adds payment term association field to the Customer's form.
 */
class CustomerFormExtension extends AbstractTypeExtension
{
    /** @var PaymentTermProviderInterface */
    protected $paymentTermProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    public function __construct(
        PaymentTermProviderInterface $paymentTermProvider,
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

            $builder->add($field->getName(), get_class($field->getType()->getInnerType()), $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CustomerType::class];
    }
}
