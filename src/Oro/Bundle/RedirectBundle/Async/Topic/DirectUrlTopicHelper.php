<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Async\Topic;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contains handy method for configuring MQ topic body.
 */
class DirectUrlTopicHelper
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function configureIdOption(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(DirectUrlMessageFactory::ID)
            ->setAllowedTypes(DirectUrlMessageFactory::ID, ['int', 'array']);
    }

    public function configureEntityClassOption(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(DirectUrlMessageFactory::ENTITY_CLASS_NAME)
            ->setAllowedTypes(DirectUrlMessageFactory::ENTITY_CLASS_NAME, 'string')
            ->setAllowedValues(
                DirectUrlMessageFactory::ENTITY_CLASS_NAME,
                static function (string $className) {
                    if (!class_exists($className) || !is_a($className, SluggableInterface::class, true)) {
                        throw new InvalidOptionsException(
                            sprintf(
                                'The option "%s" was expected to contain FQCN of the class implementing "%s"',
                                DirectUrlMessageFactory::ENTITY_CLASS_NAME,
                                SluggableInterface::class
                            )
                        );
                    }

                    return true;
                }
            );
    }

    public function configureRedirectOption(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault(DirectUrlMessageFactory::CREATE_REDIRECT, true)
            ->setAllowedTypes(DirectUrlMessageFactory::CREATE_REDIRECT, 'bool')
            ->setNormalizer(
                DirectUrlMessageFactory::CREATE_REDIRECT,
                function (Options $options, bool $value) {
                    return match ($this->configManager->get('oro_redirect.redirect_generation_strategy')) {
                        Configuration::STRATEGY_ALWAYS => true,
                        Configuration::STRATEGY_NEVER => false,
                        default => $value
                    };
                }
            );
    }
}
