<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An abstract class for a topic to resolve visibility.
 */
abstract class AbstractResolveVisibilityTopic extends AbstractTopic
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('entity_class_name')
            ->setAllowedTypes('entity_class_name', 'string')
            ->setAllowedValues('entity_class_name', function (string $value) {
                if (!$this->isManageableClass($value)) {
                    throw new InvalidOptionsException(
                        'The option "entity_class_name" is expected to contain a manageable class.'
                    );
                }

                return true;
            });

        $resolver
            ->setDefined('id')
            ->setAllowedTypes('id', ['int', 'null'])
            ->setDefault('id', function (Options $options) {
                if (!isset($options['target_class_name'])
                    && !isset($options['target_id'])
                    && !isset($options['scope_id'])) {
                    throw new InvalidOptionsException('The option "id" is expected to be not empty.');
                }

                if (!isset($options['target_id'])) {
                    throw new InvalidOptionsException('The option "target_id" is expected to be not empty.');
                }

                if (!isset($options['scope_id'])) {
                    throw new InvalidOptionsException('The option "scope_id" is expected to be not empty.');
                }
            });

        $resolver
            ->setDefined('target_class_name')
            ->setAllowedTypes('target_class_name', 'string')
            ->addNormalizer(
                'target_class_name',
                function (Options $options, ?string $value) {
                    if (!empty($options['id'])) {
                        return $value;
                    }

                    if (!$this->isManageableClass($value)) {
                        throw new InvalidOptionsException(
                            'The option "target_class_name" is expected to contain a manageable class.'
                        );
                    }

                    return $value;
                }
            );

        $resolver
            ->setDefined('target_id')
            ->setAllowedTypes('target_id', 'int');

        $resolver
            ->setDefined('scope_id')
            ->setAllowedTypes('scope_id', 'int');
    }

    private function isManageableClass(string $class): bool
    {
        return class_exists($class) && $this->managerRegistry->getManagerForClass($class);
    }
}
