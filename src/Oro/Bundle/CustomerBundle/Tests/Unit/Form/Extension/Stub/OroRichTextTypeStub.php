<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;

class OroRichTextTypeStub extends OroRichTextType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaultWysiwygOptions = [
            'statusbar' => '',
            'resize' => '',
            'width' => '',
            'height' => '',
            'plugins' => '',
            'toolbar' => '',
        ];

        $defaults = [
            'wysiwyg_options' => $defaultWysiwygOptions,
        ];

        $resolver->setDefaults($defaults);
    }
}
