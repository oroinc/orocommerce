<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageTypeStub extends AbstractType
{
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return ImageType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'checkEmptyFile' => false,
                'allowDelete' => true
            ]
        );
    }
}
