<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use Oro\Component\WebCatalog\Model\ContentVariantFormPrototype;

class TextContentVariantCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => TextContentVariantType::class,
                'prototype_name' => '__variant_idx__',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $formConfig = $form->getConfig();

        $view->vars['prototype_name'] = $options['prototype_name'];
        $view->vars['formPrototype'] = $formConfig->getAttribute('formPrototype');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->initializeContentVariantForm($builder, $options);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    protected function initializeContentVariantForm(FormBuilderInterface $builder, array $options)
    {
        $prototypeOptions = array_replace(['required' => $options['required']], $options['entry_options']);
        $prototypeForm = $builder
            ->create($options['prototype_name'], TextContentVariantType::class, $prototypeOptions)
            ->getForm();

        $builder->setAttribute('formPrototype', new ContentVariantFormPrototype($prototypeForm));
    }
}
