<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * CMS Page form type
 */
class PageType extends AbstractType
{
    public const NAME = 'oro_cms_page';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titles', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.cms.page.titles.label',
                'required' => true,
                'entry_options'  => ['constraints' => [new NotBlank()]]
            ])
            ->add('createUrlSlug', CheckboxType::class, [
                'label' => 'oro.cms.page.create_url_slug.label',
                'tooltip' => 'oro.cms.page.create_url_slug.tooltip',
                'required' => false,
                'mapped' => false,
                'data' => true,
                'attr' => [
                    'data-dependee-id' => 'oro_cms_page_create_url_slug'
                ]
            ])
            ->add('content', WYSIWYGType::class, [
                'label' => 'oro.cms.page.content.label',
                'required' => false,
            ])
            ->add('slugPrototypesWithRedirect', LocalizedSlugWithRedirectType::class, [
                'label'    => 'oro.cms.page.slug_prototypes.label',
                'required' => false,
                'source_field' => 'titles',
            ])
            ->add('doNotRenderTitle', CheckboxType::class, [
                'label' => 'oro.cms.page.do_not_render_title.label',
                'tooltip' => 'oro.cms.page.do_not_render_title.tooltip',
                'required' => false
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    public function preSetData(FormEvent $event): void
    {
        $page = $event->getData();

        if ($page instanceof Page && $page->getId()) {
            $url = $this->urlGenerator->generate('oro_cms_page_get_changed_urls', ['id' => $page->getId()]);
            $form = $event->getForm();

            if ($page->getSlugPrototypes()?->isEmpty()) {
                FormUtils::replaceField($form, 'createUrlSlug', ['data' => false]);
            }

            FormUtils::replaceField($form, 'slugPrototypesWithRedirect', ['get_changed_slugs_url' => $url]);
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->get('createUrlSlug')->getData() === false) {
            $event->getData()?->clearSlugs();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
            'csrf_token_id' => 'cms_page',
        ]);
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
