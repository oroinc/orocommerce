<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\AttributeBundle\Model\SharingType;

class SharingTypeType extends AbstractType
{
    const NAME = 'orob2b_attribute_sharing_type';

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
        $choices = [];
        foreach (SharingType::getTypes() as $type) {
            $choices[$type] = 'orob2b.attribute.form.sharing_type.' . $type;
        }

        $resolver->setDefaults(['empty_value' => false, 'choices' => $choices]);
    }
}
