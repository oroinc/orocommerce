<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

/**
 * Form type for configuring a single shipping method type.
 *
 * This form type allows administrators to enable/disable and configure options for a specific shipping method type
 * (e.g., Ground, Express) within a shipping method, providing type-specific settings and validation.
 */
class ShippingMethodTypeConfigType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_shipping_method_type_config';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['is_grouped']) {
            $builder->add('enabled', CheckboxType::class);
        } else {
            $builder->add('enabled', HiddenType::class, [
                'data' => true,
            ]);
        }
        $builder->add('type', HiddenType::class);
        $builder->add('options', $options['options_type']);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingMethodTypeConfig::class,
            'options_type' => HiddenType::class,
            'is_grouped' => false,
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                if ($data && $data->isEnabled()) {
                    return [Constraint::DEFAULT_GROUP];
                }
                return [];
            },
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }
}
