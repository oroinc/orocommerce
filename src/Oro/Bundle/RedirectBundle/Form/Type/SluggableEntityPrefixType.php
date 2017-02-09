<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Form\DataTransformer\PrefixWithRedirectToStringTransformer;
use Oro\Bundle\RedirectBundle\Form\Storage\RedirectStorage;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
     * @param RedirectStorage $storage
     */
    public function __construct(RedirectStorage $storage)
    {
        $this->storage = $storage;
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
                    'required' => !empty($options['required']),
                    'label' => '',
                    'constraints' => [new UrlSafe()]
                ]
            )
            ->add(
                self::CREATE_REDIRECT_FIELD_NAME,
                CheckboxType::class,
                [
                    'label' => 'oro.redirect.prefix_change.checkbox_label',
                ]
            );

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
        $builder->addModelTransformer(new PrefixWithRedirectToStringTransformer());
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $key = str_replace(
            ConfigManager::SECTION_VIEW_SEPARATOR,
            ConfigManager::SECTION_MODEL_SEPARATOR,
            $event->getForm()->getParent()->getName()
        );

        $this->storage->addPrefix($key, $data);
    }

    /**
     * @return bool
     */
    protected function checkIsAskStrategy()
    {
        //$this->configManager->get('oro_redirect.redirect_generation_strategy') !== Configuration::STRATEGY_ASK;
    }
}
