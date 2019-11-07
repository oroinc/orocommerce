<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub;

use Oro\Bundle\CMSBundle\Form\Type\ImageSlideType;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageSlideTypeStub extends ImageSlideType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ImageSlide::class, 'content_widget' => null]);
    }
}
