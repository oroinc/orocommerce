<?php

namespace Oro\Bundle\ShoppingListBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for invalidate totals by inventory status per product
 */
class InvalidateTotalsByInventoryStatusPerProductTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro_shopping_list.invalidate_totals_by_inventory_status_per_product';
    }

    public static function getDescription(): string
    {
        return 'Invalidates totals by inventory status per product';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define('context')
            ->allowedTypes('array')
            ->normalize(function (Options $options, $value): ?array {
                if (!isset($value['class'], $value['id'])) {
                    throw new InvalidOptionsException(
                        'The option "context" is expected to contain "id" and "class" options.'
                    );
                }
                return $value;
            });

        $resolver
            ->define('products')
            ->allowedTypes('array');
    }
}
