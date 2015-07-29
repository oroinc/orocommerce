<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;

class StubLocalizedFallbackValueCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return LocalizedFallbackValueCollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'field' => 'string',
            'type' => 'text',
            'options' => [],
            'allow_add' => true,
            'allow_delete' => true,
        ]);

        $resolver->setNormalizers([
            'type' => function () {
                return new StubLocalizedFallbackValueType();
            },
            'options' => function () {
                return [];
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
    }
}
