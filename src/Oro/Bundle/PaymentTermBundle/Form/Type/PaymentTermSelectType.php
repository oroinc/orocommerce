<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentTermSelectType extends AbstractType
{
    public const NAME = 'oro_payment_term_select';

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
