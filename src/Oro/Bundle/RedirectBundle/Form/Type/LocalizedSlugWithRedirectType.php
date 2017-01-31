<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SlugPrototypesWithRedirect;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LocalizedSlugWithRedirectType extends AbstractType
{
    const NAME = 'oro_redirect_localized_slug_with_redirect';
    const SLUG_PROTOTYPES_FIELD_NAME = 'slugPrototypes';
    const CREATE_REDIRECT_FIELD_NAME = 'createRedirect';

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $constraints = [new UrlSafe()];
        if (!empty($options['required'])) {
            $constraints[] = new NotBlank();
        }

        $builder
            ->add(
                self::SLUG_PROTOTYPES_FIELD_NAME,
                LocalizedSlugType::NAME,
                [
                    'required' => !empty($options['required']),
                    'options'  => ['constraints' => $constraints],
                    'source_field' => $options['source_field'],
                    'label' => false,
                    'slug_suggestion_enabled' => $options['slug_suggestion_enabled'],
                ]
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
                self::CREATE_REDIRECT_FIELD_NAME,
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
            'create_redirect_enabled' => false,
            'slug_suggestion_enabled' => false,
            'data_class' => SlugPrototypesWithRedirect::class,
        ]);
        $resolver->setRequired('source_field');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->has(self::CREATE_REDIRECT_FIELD_NAME)) {
            $fullName = $view->vars['full_name'];
            $valuesField = sprintf('[name^="%s[%s][values]"]', $fullName, self::SLUG_PROTOTYPES_FIELD_NAME);

            $view->vars['confirm_slug_change_component_options'] = [
                'slugFields' => $valuesField,
                'createRedirectCheckbox' => sprintf('[name^="%s[%s]"]', $fullName, self::CREATE_REDIRECT_FIELD_NAME)
            ];
        }
    }

    /**
     * @param mixed $data
     * @return bool
     */
    protected function isNotEmptyCollection($data)
    {
        return $data->getSlugPrototypes() instanceof Collection && !$data->getSlugPrototypes()->isEmpty();
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
