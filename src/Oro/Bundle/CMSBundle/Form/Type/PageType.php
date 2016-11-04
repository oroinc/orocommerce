<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                'title',
                TextType::class,
                [
                    'label' => 'oro.cms.page.title.label',
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
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Page $page */
            $page = $event->getData();
            $form = $event->getForm();

            if ($page && $page->getId()) {
                $form->add(
                    'slug',
                    SlugType::NAME,
                    [
                        'label' => 'oro.redirect.slug.entity_label',
                        'required' => false,
                        'mapped' => false,
                        'type' => 'update',
                        'current_slug' => $page->getCurrentSlug()->getUrl()
                    ]
                );
            } else {
                $form->add(
                    'slug',
                    SlugType::NAME,
                    [
                        'label' => 'oro.redirect.slug.entity_label',
                        'required' => false,
                        'mapped' => false,
                        'type' => 'create'
                    ]
                );
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $slugData = $event->getForm()->get('slug')->getData();
            /** @var Page $page */
            $page = $event->getData();

            if ($slugData['mode'] === 'new') {
                if (isset($slugData['redirect']) && $slugData['redirect']) {
                    // Leave the old slug for page. And add a new slug as current for page
                    $slug = new Slug();
                    $slug->setUrl($slugData['slug']);
                    $page->setCurrentSlug($slug);
                } else {
                    // Change current slug url
                    $page->setCurrentSlugUrl($slugData['slug']);
                }
            }
        });
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
