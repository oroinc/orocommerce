<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Async\Topic;

use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to remove Slug URLs for the entities of specified type.
 */
class RemoveDirectUrlForEntityTypeTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.redirect.remove_direct_url.entity_type';
    }

    public static function getDescription(): string
    {
        return 'Remove Slug URLs for the entities of specified type.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('body')
            ->setAllowedTypes('body', 'string')
            ->setAllowedValues(
                'body',
                static function (string $className) {
                    if (!class_exists($className) || !is_a($className, SluggableInterface::class, true)) {
                        throw new InvalidOptionsException(
                            sprintf(
                                'The option "body" was expected to contain FQCN of the class implementing "%s"',
                                SluggableInterface::class
                            )
                        );
                    }

                    return true;
                }
            )
            ->setInfo('body', sprintf('Entity fully qualified class name implementing %s.', SluggableInterface::class));
    }
}
