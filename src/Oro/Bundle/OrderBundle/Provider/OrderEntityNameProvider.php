<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a name for {@see Order} entity.
 */
class OrderEntityNameProvider implements EntityNameProviderInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function getName($format, $locale, $entity): false|string
    {
        if (!$entity instanceof Order) {
            return false;
        }

        $localeCode = $locale instanceof Localization ? $locale->getLanguageCode() : $locale;
        $translationKey = $format === EntityNameProviderInterface::SHORT
            ? 'oro.order.entity_name.short'
            : 'oro.order.entity_name.full';

        return $this->translator->trans(
            id: $translationKey,
            parameters: [
                '%order_identifier%' => (string) ($entity->getIdentifier() ?? $entity->getId()),
            ],
            locale: $localeCode
        );
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias): false|string
    {
        if (!is_a($className, Order::class, true)) {
            return false;
        }

        $localeCode = $locale instanceof Localization ? $locale->getLanguageCode() : $locale;
        $translationKey = $format === EntityNameProviderInterface::SHORT
            ? 'oro.order.entity_name.short'
            : 'oro.order.entity_name.full';

        $orderName = $this->translator->trans(id: $translationKey, locale: $localeCode);

        return sprintf(
            'CONCAT(%s)',
            str_replace(
                ['%order_identifier%'],
                [
                    sprintf("', %s.identifier, '", $alias),
                ],
                (string)(new Expr())->literal($orderName)
            )
        );
    }
}
