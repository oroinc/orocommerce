<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugType extends AbstractType
{
    const NAME = 'oro_redirect_slug';

    /**
     * @var SlugifyFormHelper
     */
    private $slugifyFormHelper;

    public function __construct(SlugifyFormHelper $slugifyFormHelper)
    {
        $this->slugifyFormHelper = $slugifyFormHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'slug_suggestion_enabled' => true,
            'slugify_route' => 'oro_api_slugify_slug',
        ]);
        $resolver->setRequired('source_field');
        $resolver->setDefined('constraints');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->slugifyFormHelper->addSlugifyOptions($view, $options);
    }
}
