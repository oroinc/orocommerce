<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\AccountType;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AccountFormExtension extends AbstractTypeExtension
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
        $account = $builder->getData();
        if (!$account || !$account->getGroup()) {
            return;
        }

        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames($options['data_class']);
        if (!$associationNames) {
            return;
        }

        $paymentTermByAccountGroup = $this->paymentTermProvider->getAccountGroupPaymentTerm($account->getGroup());
        if (!$paymentTermByAccountGroup) {
            return;
        }

        foreach ($associationNames as $associationName) {
            if (!$builder->has($associationName)) {
                return;
            }

            $field = $builder->get($associationName);
            $options = $field->getOptions();

            $options['configs']['placeholder'] = $this->translator->trans(
                'oro.paymentterm.account.account_group_defined',
                [
                    '{{ payment_term }}' => $paymentTermByAccountGroup->getLabel(),
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
        return AccountType::NAME;
    }
}
