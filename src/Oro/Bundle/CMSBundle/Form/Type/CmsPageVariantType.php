<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\FormBundle\Form\Type\CheckboxType;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * CMS page content variant form type
 */
class CmsPageVariantType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cmsPage', PageSelectType::class, [
                'label' => 'oro.cms.page.entity_label',
                'required' => true,
                'constraints' => [new NotBlank()]
            ])
            ->add('doNotRenderTitle', CheckboxType::class, [
                'label' => 'oro.webcatalog.contentvariant.do_not_render_title.label',
                'tooltip' => 'oro.webcatalog.contentvariant.do_not_render_title.tooltip',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return PageVariantType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_cms_page_variant';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'content_variant_type' => CmsPageContentVariantType::TYPE,
        ]);
    }
}
