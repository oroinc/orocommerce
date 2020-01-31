<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manage slugs for each of system localizations.
 */
class LocalizedSlugType extends AbstractType
{
    const NAME = 'oro_redirect_localized_slug';

    /**
     * @var SlugifyFormHelper
     */
    private $slugifyFormHelper;

    /**
     * @var SlugGenerator
     */
    private $slugGenerator;

    /**
     * @param SlugifyFormHelper $slugifyFormHelper
     * @param SlugGenerator $slugGenerator
     */
    public function __construct(SlugifyFormHelper $slugifyFormHelper, SlugGenerator $slugGenerator)
    {
        $this->slugifyFormHelper = $slugifyFormHelper;
        $this->slugGenerator = $slugGenerator;
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
        return LocalizedFallbackValueCollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Change update at of owning entity on slug collection change
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                while ($form->getParent()) {
                    $form = $form->getParent();
                }

                $data = $form->getData();
                if ($data instanceof UpdatedAtAwareInterface) {
                    $data->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
                }

                if (isset($form[$options['source_field']])) {
                    $localizedSources = $form[$options['source_field']]->getData();
                    $localizedSlugs = $event->getForm()->getData();

                    if ($localizedSources instanceof Collection && $localizedSlugs instanceof Collection) {
                        $this->fillDefaultSlugs($localizedSources, $localizedSlugs);
                    }
                }
            }
        );
    }

    /**
     * @param Collection $localizedSources
     * @param Collection $localizedSlugs
     */
    public function fillDefaultSlugs(Collection $localizedSources, Collection $localizedSlugs): void
    {
        /** @var LocalizedFallbackValue $localizedSource */
        foreach ($localizedSources as $localizedSource) {
            if (!$localizedSource->getString()) {
                continue;
            }

            /** @var LocalizedFallbackValue $localizedSlug */
            foreach ($localizedSlugs as $localizedSlug) {
                if ($localizedSlug->getString()
                    && $localizedSource->getLocalization() === $localizedSlug->getLocalization()) {
                    // Skips creating default slug as it is already defined.
                    continue 2;
                }
            }

            $localizedSlug = clone $localizedSource;
            $localizedSlug->setString($this->slugGenerator->slugify($localizedSource->getString()));
            $localizedSlugs->add($localizedSlug);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'slug_suggestion_enabled' => true,
            'slugify_route' => 'oro_api_slugify_slug',
            'exclude_parent_localization' => true,
        ]);
        $resolver->setDefined('source_field');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->slugifyFormHelper->addSlugifyOptionsLocalized($view, $options);
    }
}
