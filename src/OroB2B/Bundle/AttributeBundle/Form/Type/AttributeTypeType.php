<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AttributeTypeType extends AbstractType
{
    const NAME = 'orob2b_attribute_type';

    /**
     * @var AttributeTypeRegistry
     */
    protected $registry;

    /**
     * @param AttributeTypeRegistry $registry
     */
    public function __construct(AttributeTypeRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'empty_value' => 'orob2b.attribute.attribute_type.empty',
                'choices' => $this->getChoices()
            ]
        );
    }

    /**
     * @return array
     */
    protected function getChoices()
    {
        $choices = [];
        foreach ($this->registry->getTypes() as $type) {
            $choices[$type->getName()] = 'orob2b.attribute.attribute_type.' . $type->getName();
        }

        return $choices;
    }
}
