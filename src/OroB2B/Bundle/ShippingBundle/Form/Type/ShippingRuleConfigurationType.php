<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraint;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

class ShippingRuleConfigurationType extends AbstractType
{
    const NAME = 'orob2b_shipping_rule_configuration';
    const ENABLED_VALIDATION_GROUP = 'Enabled';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('enabled', CheckboxType::class);
        $builder->add('type', HiddenType::class);
        $builder->add('method', HiddenType::class);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingRuleConfiguration::class,
            'validation_groups' => function (FormInterface $form) {
                /** @var ShippingRuleConfiguration $data */
                $data = $form->getData();
                if ($data && $data->getEnabled()) {
                    return [Constraint::DEFAULT_GROUP, static::ENABLED_VALIDATION_GROUP];
                }
                return [Constraint::DEFAULT_GROUP];
            },
        ]);
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
