<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType;

class NotLocalizedSelectCollectionType extends AbstractType
{
    const NAME = 'orob2b_not_localized_select_collection';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'options' => [
                    'is_default_type' => 'hidden',
                    'value_type' => FallbackValueType::NAME,
                    'required' => false,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'orob2b_options_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
