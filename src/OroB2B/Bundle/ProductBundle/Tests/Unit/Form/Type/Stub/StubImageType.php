<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;

class StubImageType extends AbstractType
{
    /**
     * @return string
     */
    public function getName()
    {
        return ImageType::NAME;
    }
}
