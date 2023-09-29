<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Provides validation groups for usage with checkout taking into account its source entity.
 *
 * Substitutes placeholder "%from_alias%" with "_from_entityalias" where "entityalias" is a configured entity alias
 * provided by {@see EntityAliasResolver}. For example:
 *  1. ['Default', 'checkout_pre_order_create%from_alias%'] for ShoppingList source entity will become:
 *      ['Default', 'checkout_pre_order_create_from_shoppinglist']
 *  2. ['Default', 'checkout_pre_order_create%from_alias%'] when null source entity will become:
 *      ['Default', 'checkout_pre_order_create']
 *
 * Transforms array of validation group names into {@see GroupSequence}, for example:
 *  1. [['Default', 'custom_group_name'], 'another_group'] will be become:
 *      [new GroupSequence(['Default', 'custom_group_name']), 'another_group']
 *  2. ['Default', 'custom_group_name', 'another_group'] will become:
 *      ['Default', 'custom_group_name', 'another_group']
 */
class CheckoutValidationGroupsBySourceEntityProvider
{
    private EntityAliasResolver $entityAliasResolver;

    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * @param array<string|string[]|GroupSequence> $validationGroups
     * @param CheckoutSourceEntityInterface|string|null $checkoutSourceEntity Source entity object or its FQCN.
     *
     * @return array<string|GroupSequence>
     */
    public function getValidationGroupsBySourceEntity(
        array $validationGroups,
        CheckoutSourceEntityInterface|string|null $checkoutSourceEntity
    ): array {
        $placeholderValue = '';
        if ($checkoutSourceEntity !== null) {
            $sourceEntityClass = $checkoutSourceEntity;
            if (is_object($checkoutSourceEntity)) {
                $sourceEntityClass = ClassUtils::getClass($checkoutSourceEntity);
            }
            $sourceEntityAlias = $this->entityAliasResolver->getAlias($sourceEntityClass);
            $placeholderValue = '_from_' . $sourceEntityAlias;
        }

        return ValidationGroupUtils::resolveValidationGroups($validationGroups, ['%from_alias%' => $placeholderValue]);
    }
}
