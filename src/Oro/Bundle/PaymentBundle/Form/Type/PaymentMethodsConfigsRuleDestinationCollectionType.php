<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodsConfigsRuleDestinationCollectionType extends AbstractType
{
    public const NAME = 'oro_payment_methods_configs_rule_destination_collection';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => PaymentMethodsConfigsRuleDestinationType::class,
                'show_form_when_empty' => true,
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
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
}
