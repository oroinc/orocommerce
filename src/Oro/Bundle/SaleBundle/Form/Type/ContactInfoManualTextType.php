<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactInfoManualTextType extends AbstractType
{
    public const NAME = 'oro_sale_contact_info_manual_text';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $key = Configuration::getConfigKeyByName(Configuration::ALLOW_USER_CONFIGURATION);
        $configValue = $this->configManager->get($key) ? false : true;

        $resolver->setDefaults([
            'disabled' => $configValue
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextareaType::class;
    }
}
