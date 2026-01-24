<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting a payment term with inline creation support.
 *
 * This form type provides an autocomplete field for selecting existing payment terms
 * and allows users to create new payment terms inline without leaving the form.
 */
class PaymentTermSelectType extends AbstractType
{
    const NAME = 'oro_payment_term_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => PaymentTermType::class,
                'create_form_route' => 'oro_payment_term_create',
                'configs' => [
                    'placeholder' => 'oro.paymentterm.form.choose',
                    'allowClear' => true,
                ]
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
