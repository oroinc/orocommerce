<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to resolve visibility for a customer when it is changed.
 */
class VisibilityOnChangeCustomerTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro_visibility.visibility.change_customer';
    }

    public static function getDescription(): string
    {
        return 'Resolve visibility for a customer when it is changed.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('id')
            ->setAllowedTypes('id', 'int');
    }
}
