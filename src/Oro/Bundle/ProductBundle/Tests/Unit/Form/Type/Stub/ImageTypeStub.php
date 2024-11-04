<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageTypeStub extends AbstractType
{
    /**
     * @return string
     */
    #[\Override]
    public function getBlockPrefix(): string
    {
        return ImageType::NAME;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'checkEmptyFile' => false,
                'allowDelete' => true,
                'allowUpdate' => true,
                'constraints' => [new ProductImage()],
            ]
        );
    }
}
