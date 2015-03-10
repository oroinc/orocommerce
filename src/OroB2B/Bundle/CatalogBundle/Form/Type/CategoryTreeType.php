<?php

namespace OroB2B\Bundle\CatalogBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

class CategoryTreeType extends AbstractType
{
    const NAME = 'orob2b_catalog_category_tree';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'class'    => 'OroB2BCatalogBundle:Category',
            'multiple' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityIdentifierType::NAME;
    }
}
