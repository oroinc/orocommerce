<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Symfony\Component\Form\AbstractType;
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
    const NAME = 'oro_cms_page';

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label'    => 'oro.cms.page.titles.label',
                    'required' => true,
                    'entry_options'  => ['constraints' => [new NotBlank()]]
                ]
            )
            ->add(
                'content',
                WYSIWYGType::class,
                [
                    'label' => 'oro.cms.page.content.label',
                    'required' => false,
                ]
            )
            ->add(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::class,
                [
                    'label'    => 'oro.cms.page.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'titles'
                ]
            )
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetDataListener']);
    }

    public function preSetDataListener(FormEvent $event)
    {
        $page = $event->getData();

        if ($page instanceof Page && $page->getId()) {
            $url = $this->urlGenerator->generate('oro_cms_page_get_changed_urls', ['id' => $page->getId()]);

            $event->getForm()->add(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::class,
                [
                    'label'    => 'oro.cms.page.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'titles',
                    'get_changed_slugs_url' => $url
                ]
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
            'csrf_token_id' => 'cms_page',
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
