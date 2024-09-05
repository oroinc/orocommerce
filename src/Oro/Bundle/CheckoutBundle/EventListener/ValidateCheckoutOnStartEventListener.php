<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates line items to check if a checkout can be started.
 */
class ValidateCheckoutOnStartEventListener
{
    private ValidatorInterface $validator;

    private CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider;

    /** @var array<string|array<string>> */
    private array $validationGroups = [['Default', 'checkout_start%from_alias%']];

    public function __construct(
        ValidatorInterface $validator,
        CheckoutValidationGroupsBySourceEntityProvider $checkoutValidationGroupsBySourceEntityProvider
    ) {
        $this->validator = $validator;
        $this->validationGroupsProvider = $checkoutValidationGroupsBySourceEntityProvider;
    }

    /**
     * @param array<string|array<string>> $validationGroups
     */
    public function setValidationGroups(array $validationGroups): void
    {
        $this->validationGroups = $validationGroups;
    }

    private function validateOnStart(ExtendableConditionEvent $event, object $entity, array $validationGroups): void
    {
        $violationList = $this->validator->validate($entity, null, $validationGroups);
        foreach ($violationList as $violation) {
            $event->addError($violation->getMessage(), $violation);
        }
    }

    public function onStart(ExtendableConditionEvent $event): void
    {
        $context = $event->getContext();
        $checkout = $context?->offsetGet('checkout');
        if (!$context?->offsetGet('checkout') instanceof Checkout) {
            return;
        }

        $validationGroups = $this->validationGroupsProvider
            ->getValidationGroupsBySourceEntity($this->validationGroups, $checkout->getSourceEntity());

        $this->validateOnStart($event, $checkout, $validationGroups);
    }

    public function onStartFromShoppingList(ExtendableConditionEvent $event): void
    {
        $shoppingList = $event->getContext()?->offsetGet('shoppingList');
        if (!$shoppingList instanceof ShoppingList) {
            return;
        }

        $validationGroups = $this->validationGroupsProvider
            ->getValidationGroupsBySourceEntity($this->validationGroups, $shoppingList);

        $this->validateOnStart($event, $shoppingList, $validationGroups);
    }
}
