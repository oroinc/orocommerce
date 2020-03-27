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
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     */
    public function __construct(SlugifyFormHelper $slugifyFormHelper)
    {
        $this->slugifyFormHelper = $slugifyFormHelper;
    }

    /**
     * @param SlugGenerator $slugGenerator
     */
    public function setSlugGenerator(SlugGenerator $slugGenerator)
    {
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

                if ($form->has($options['source_field'])) {
                    $localizedSources = $form->get($options['source_field'])->getData();
                    $localizedSlugs = $event->getForm()->getData();

                    if ($localizedSources instanceof Collection && $localizedSlugs instanceof Collection) {
                        $this->fillDefaultSlugs($localizedSources, $localizedSlugs);
                    }
                }
            }
        );
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $localizedSources
     * @param Collection|LocalizedFallbackValue[] $localizedSlugs
     */
    public function fillDefaultSlugs(Collection $localizedSources, Collection $localizedSlugs): void
    {
        foreach ($localizedSources as $localizedSource) {
            if (!$localizedSource->getString() || $this->isSlugExists($localizedSlugs, $localizedSource)) {
                continue;
            }

            $localizedSlug = clone $localizedSource;
            $localizedSlug->setString($this->slugGenerator->slugify($localizedSource->getString()));
            $localizedSlugs->add($localizedSlug);
        }
    }

    /**
     * Skips creating default slug as it is already defined
     *
     * @param Collection|LocalizedFallbackValue[] $localizedSlugs
     * @param LocalizedFallbackValue $localizedSource
     * @return bool
     */
    private function isSlugExists(Collection $localizedSlugs, LocalizedFallbackValue $localizedSource): bool
    {
        foreach ($localizedSlugs as $localizedSlug) {
            if (!$localizedSlug->getId() && !$localizedSlug->getString()) {
                continue;
            }

            if ($localizedSource->getLocalization() === $localizedSlug->getLocalization()) {
                return true;
            }

            if ($localizedSource->getLocalization() &&
                $localizedSlug->getLocalization() &&
                $localizedSource->getLocalization()->getId() === $localizedSlug->getLocalization()->getId()
            ) {
                return true;
            }
        }

        return false;
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
