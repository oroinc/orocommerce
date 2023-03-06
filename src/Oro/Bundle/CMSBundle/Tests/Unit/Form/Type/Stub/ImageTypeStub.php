<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageTypeStub extends EntityTypeStub
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('checkEmptyFile', false);
        $resolver->setDefault('allowDelete', true);
    }
}
