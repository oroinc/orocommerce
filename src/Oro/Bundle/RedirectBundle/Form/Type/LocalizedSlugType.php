<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugType extends AbstractType
{
    const NAME = 'oro_redirect_localized_slug';
    const CREATE_REDIRECT_OPTION_NAME = 'createRedirect';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
            function (FormEvent $event) {
                $form = $event->getForm();
                if ($form->getParent()) {
                    $data = $form->getParent()->getData();
                    if ($data instanceof UpdatedAtAwareInterface) {
                        $data->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
                    }
                }
            }
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        if ($this->isRedirectConfirmationEnabled($event->getForm()->getConfig()->getOptions())
            && $this->isNotEmptyCollection($event->getData())
        ) {
            $event->getForm()->add(
                self::CREATE_REDIRECT_OPTION_NAME,
                CheckboxType::class,
                [
                    'label' => 'oro.redirect.confirm_slug_change.checkbox_label',
                    'data' => true,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'slug_suggestion_enabled' => false,
            'slugify_route' => 'oro_api_slugify_slug',
            'create_redirect_enabled' => false,
            'localized_slug_component' => 'ororedirect/js/app/components/localized-slug-component'
        ]);
        $resolver->setDefined('source_field');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['slug_suggestion_enabled']
            && isset($options['source_field'])
            && !empty($view->parent->vars['full_name'])
        ) {
            $sourceFieldName = sprintf('%s[%s]', $view->parent->vars['full_name'], $options['source_field']);
            $targetFieldName = $view->vars['full_name'];

            $view->vars['localized_slug_component'] = $options['localized_slug_component'];
            $view->vars['localized_slug_component_options']['slugify_component_options'] = [
                'source' => sprintf('[name^="%s[values]"]', $sourceFieldName),
                'target' => sprintf('[name^="%s[values]"]', $targetFieldName),
                'slugify_route' => $options['slugify_route'],
            ];
        }

        if ($this->isRedirectConfirmationEnabled($options) && $this->isNotEmptyCollection($form->getData())) {
            $fullName = $view->vars['full_name'];
            $valuesField = sprintf('[name^="%s[values]"]', $fullName);

            $view->vars['localized_slug_component'] = $options['localized_slug_component'];
            $view->vars['localized_slug_component_options']['confirmation_component_options'] = [
                'slugFields' => $valuesField,
                'createRedirectCheckbox' => sprintf('[name^="%s[%s]"]', $fullName, self::CREATE_REDIRECT_OPTION_NAME)
            ];
        }
    }

    /**
     * @param mixed $data
     * @return bool
     */
    protected function isNotEmptyCollection($data)
    {
        return $data instanceof Collection && !$data->isEmpty();
    }

    /**
     * @param array|\ArrayAccess $options
     * @return bool
     */
    protected function isRedirectConfirmationEnabled($options)
    {
        return $options['create_redirect_enabled']
            && $this->configManager->get('oro_redirect.redirect_generation_strategy') === Configuration::STRATEGY_ASK;
    }
}
