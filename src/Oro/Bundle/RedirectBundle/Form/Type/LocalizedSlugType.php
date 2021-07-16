<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Helper\SlugifyEntityHelper;
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
     * @var SlugifyEntityHelper
     */
    private $slugifyEntityHelper;

    public function __construct(SlugifyFormHelper $slugifyFormHelper, SlugifyEntityHelper $slugifyEntityHelper)
    {
        $this->slugifyFormHelper = $slugifyFormHelper;
        $this->slugifyEntityHelper = $slugifyEntityHelper;
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
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * Change update at of owning entity on slug collection change
     */
    public function onPostSubmit(FormEvent $event): void
    {
        $sourceFieldName = $event->getForm()->getConfig()->getOption('source_field');
        $form = $event->getForm()->getRoot();
        $this->updateDateTime($form->getData());
        if ($form->has($sourceFieldName)) {
            $localizedSources = $form->get($sourceFieldName)->getData();
            $localizedSlugs = $event->getForm()->getData();
            if ($localizedSources instanceof Collection && $localizedSlugs instanceof Collection) {
                $this->slugifyEntityHelper->fillFromSourceField($localizedSources, $localizedSlugs);
            }
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

    /**
     * @throws \Exception
     */
    private function updateDateTime($entity): void
    {
        if ($entity instanceof UpdatedAtAwareInterface) {
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }
}
