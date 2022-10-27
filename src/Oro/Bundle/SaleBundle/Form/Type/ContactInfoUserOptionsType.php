<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SaleBundle\Provider\OptionProviderWithDefaultValueInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactInfoUserOptionsType extends AbstractType
{
    const NAME = 'oro_sale_contact_info_user_option';

    /**
     * @var OptionProviderWithDefaultValueInterface
     */
    protected $optionsProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(OptionProviderWithDefaultValueInterface $optionsProvider, ConfigManager $configManager)
    {
        $this->optionsProvider = $optionsProvider;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $key = Configuration::getConfigKeyByName(Configuration::ALLOW_USER_CONFIGURATION);
        $configValue = $this->configManager->get($key) ? false : true;
        $choices = $this->optionsProvider->getOptions();

        $resolver->setDefaults([
            'choices' => array_combine($choices, $choices),
            'multiple' => false,
            'disabled' => $configValue
        ]);

        $resolver->setNormalizer('choice_label', function () {
            return function ($optionValue) {
                return sprintf('oro.sale.contact_info_user_options.type.%s.label', $optionValue);
            };
        });
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $options = $event->getData();
            if (empty($options)) {
                $options = $this->optionsProvider->getDefaultOption();
                $event->setData($options);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
