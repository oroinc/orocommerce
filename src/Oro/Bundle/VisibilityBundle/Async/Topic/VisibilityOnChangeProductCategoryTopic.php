<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to resolve visibility for a product when its category is changed.
 */
class VisibilityOnChangeProductCategoryTopic extends AbstractTopic
{
    public const NAME = 'oro_visibility.visibility.change_product_category';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Resolve visibility for a product when its category is changed.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('id')
            ->setAllowedTypes('id', ['int', 'int[]']);

        $resolver
            ->setDefault('scheduleReindex', false)
            ->setAllowedTypes('scheduleReindex', 'bool');
    }
}
