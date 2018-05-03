<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopeCollectionTypeStub extends ScopeCollectionType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('allow_add', true);
        $resolver->setDefault('allow_delete', true);
        $resolver->setNormalizer('entry_type', function () {
            return ScopeTypeStub::class;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
