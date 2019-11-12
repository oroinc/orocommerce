<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub;

use Oro\Bundle\CMSBundle\Form\Type\ImageSlideCollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageSlideCollectionTypeStub extends ImageSlideCollectionType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['entry_type' => ImageSlideTypeStub::class]);
    }
}
