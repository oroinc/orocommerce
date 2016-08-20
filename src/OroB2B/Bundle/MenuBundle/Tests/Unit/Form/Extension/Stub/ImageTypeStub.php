<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

class ImageTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', 'file');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_image';
    }
}
