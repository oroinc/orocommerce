<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Alphanumeric;

class InitAttributeType extends AbstractType
{
    const NAME = 'orob2b_attribute_init';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'code',
                'text',
                [
                    'label' => 'orob2b.attribute.code.label',
                    'constraints' => [new NotBlank(), new Length(['min' => 3, 'max' => 255]), new Alphanumeric()]
                ]
            )
            ->add(
                'type',
                AttributeTypeType::NAME,
                ['label' => 'orob2b.attribute.type.label', 'constraints' => [new NotBlank()]]
            )
            ->add(
                'localized',
                'checkbox',
                ['label' => 'orob2b.attribute.localized.label', 'required' => false]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
