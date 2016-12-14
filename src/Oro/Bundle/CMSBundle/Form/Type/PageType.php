<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Form\AbstractType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PageType extends AbstractType
{
    const NAME = 'oro_cms_page';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label'    => 'oro.cms.page.titles.label',
                    'required' => true,
                    'options'  => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'content',
                OroRichTextType::NAME,
                [
                    'label' => 'oro.cms.page.content.label',
                    'required' => false,
                    'wysiwyg_options' => [
                        'statusbar' => true,
                        'resize' => true,
                    ]
                ]
            )
            ->add(
                'slugPrototypes',
                LocalizedSlugType::NAME,
                [
                    'label'    => 'oro.cms.page.slug_prototypes.label',
                    'required' => false,
                    'options'  => ['constraints' => [new UrlSafe()]],
                    'slug_suggestion_enabled' => true,
                    'source_field' => 'titles',
                    'create_redirect_enabled' => true
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Page::class
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
