<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugTypeStub extends LocalizedFallbackValueCollectionTypeStub
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return LocalizedSlugType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'target_field_name' => ''
        ]);
    }
}
