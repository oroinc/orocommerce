<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ImageTypeStub extends AbstractType
{
    const NAME = 'oro_image';

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
        return self::NAME;
    }
}
