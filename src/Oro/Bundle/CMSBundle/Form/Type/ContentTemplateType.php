<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
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
                'previewImage',
                ImageType::class,
                [
                    'label' => 'oro.cms.contenttemplate.preview_image.label',
                    'required' => false,
                    'attr' => [
                        'class' => 'hide'
                    ]
                ]
            )
            ->add(
                'content',
                WYSIWYGType::class,
                [
                    'label' => 'oro.cms.contenttemplate.content.label',
                    'required' => true,
                    'disable_isolation' => true,
                    'builder_plugins' => [
                        'template-screenshot-plugin' => [
                            'jsmodule' => 'orocms/js/app/grapesjs/plugins/screenshot',
                            'previewFieldName' => 'oro_cms_content_template[previewImage][file]',
                            'width' => 1100,
                            'height' => 450
                        ]
                    ]
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
