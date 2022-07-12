<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\TagBundle\Form\Type\TagSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CMS Content Template Form Type
 */
class ContentTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'oro.cms.contenttemplate.name.label',
                    'required' => true
                ]
            )
            ->add(
                'content',
                WYSIWYGType::class,
                [
                    'label' => 'oro.cms.contenttemplate.content.label',
                    'required' => true
                ]
            )
            ->add(
                'tags',
                TagSelectType::class,
                [
                    'label' => 'oro.cms.contenttemplate.tags.label',
                    'required' => false,
                    'mapped' => false
                ]
            )
            ->add(
                'enabled',
                CheckboxType::class,
                [
                    'label' => 'oro.cms.contenttemplate.enabled.label',
                    'required' => false
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContentTemplate::class,
            'csrf_token_id' => 'cms_content_template',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_cms_content_template';
    }
}
