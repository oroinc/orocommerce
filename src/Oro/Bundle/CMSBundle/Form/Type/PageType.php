<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
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
                    'constraints' => [new NotBlank()],
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
                'slugs',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label'    => 'oro.cms.page.slugs.label',
                    'required' => true,
                    'options'  => ['constraints' => [new NotBlank()]],
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
