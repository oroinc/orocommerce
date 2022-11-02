<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Form\Storage\RedirectStorage;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing sluggable entities prefix.
 * Used in system configuration.
 */
class SluggableEntityPrefixType extends AbstractType
{
    const NAME = 'oro_redirect_sluggable_prefix';
    const PREFIX_FIELD_NAME = 'prefix';
    const CREATE_REDIRECT_FIELD_NAME = 'createRedirect';

    /**
     * @var RedirectStorage
     */
    protected $storage;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(RedirectStorage $storage, ConfigManager $configManager)
    {
        $this->storage = $storage;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => PrefixWithRedirect::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PREFIX_FIELD_NAME,
                TextType::class,
                [
                    'label' => '',
                    'required' => $options['required'],
                    'constraints' => $options['constraints']
                ]
            )
            ->add(
                self::CREATE_REDIRECT_FIELD_NAME,
                CheckboxType::class,
                [
                    'label' => 'oro.redirect.prefix_change.checkbox_label',
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    public function onPreSubmit(FormEvent $event)
    {
        // Value from model should be converted from string to appropriate array for `Use Default` case
        $data = $event->getData();
        if (is_string($data)) {
            $data = ['prefix' => $data];
        }

        $event->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['isAskStrategy'] = $this->checkIsAskStrategy();
        $view->vars['askStrategyName'] = Configuration::STRATEGY_ASK;
    }

    public function onSubmit(FormEvent $event)
    {
        $data = $event->getForm()->getViewData();
        if ($data) {
            $key = str_replace(
                ConfigManager::SECTION_VIEW_SEPARATOR,
                ConfigManager::SECTION_MODEL_SEPARATOR,
                $event->getForm()->getParent()->getName()
            );

            $this->storage->addPrefix($key, $data);
        }
    }

    /**
     * @return bool
     */
    protected function checkIsAskStrategy()
    {
        return $this->configManager->get('oro_redirect.redirect_generation_strategy') === Configuration::STRATEGY_ASK;
    }
}
