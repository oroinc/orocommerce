<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\CheckoutBundle\Resolver\ShoppingListToCheckoutValidationGroupResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates line items to check if a checkout can be started.
 */
class ValidateCheckoutOnStartEventListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    private ValidatorInterface $validator;

    private CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider;

    private InvalidShoppingListLineItemsProvider $invalidShoppingListLineItemsProvider;

    private ShoppingListToCheckoutValidationGroupResolver $checkoutValidationGroupResolver;

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

    public function setInvalidShoppingListLineItemsProvider(
        InvalidShoppingListLineItemsProvider $invalidShoppingListLineItemsProvider
    ): void {
        $this->invalidShoppingListLineItemsProvider = $invalidShoppingListLineItemsProvider;
    }

    public function setCheckoutValidationGroupResolver(
        ShoppingListToCheckoutValidationGroupResolver $checkoutValidationGroupResolver
    ): void {
        $this->checkoutValidationGroupResolver = $checkoutValidationGroupResolver;
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
        $data = $event->getData();
        $checkout = $data?->offsetGet('checkout');
        if (!$data?->offsetGet('checkout') instanceof Checkout) {
            return;
        }

        $validationGroups = $this->validationGroupsProvider
            ->getValidationGroupsBySourceEntity($this->validationGroups, $checkout->getSourceEntity());

        $this->validateOnStart($event, $checkout, $validationGroups);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onStartFromShoppingList(ExtendableConditionEvent $event): void
    {
        if (isset($this->checkoutValidationGroupResolver) && !$this->checkoutValidationGroupResolver->isApplicable()) {
            return;
        }

        $shoppingList = $event->getData()?->offsetGet('shoppingList');
        if (!$shoppingList instanceof ShoppingList) {
            return;
        }

        if ($this->isFeaturesEnabled() && isset($this->invalidShoppingListLineItemsProvider)) {
            $validationResult = $this->invalidShoppingListLineItemsProvider->getInvalidItemsViolations(
                $shoppingList->getLineItems(),
                ShoppingListToCheckoutValidationGroupResolver::TYPE
            );

            $errors = $validationResult[InvalidShoppingListLineItemsProvider::ERRORS];

            foreach ($errors as $errorItem) {
                /**
                 * Process main error messages
                 *
                 * @var ConstraintViolationInterface $error
                 */
                foreach ($errorItem['messages'] as $error) {
                    $event->addError($error->getMessage(), $error);
                }

                // Process subData messages if they exist
                if (!empty($errorItem['subData'])) {
                    foreach ($errorItem['subData'] as $errorSubData) {
                        foreach ($errorSubData['messages'] as $subDataMessage) {
                            $event->addError($subDataMessage->getMessage(), $subDataMessage);
                        }
                    }
                }
            }

            return;
        }

        $validationGroups = $this->validationGroupsProvider
            ->getValidationGroupsBySourceEntity($this->validationGroups, $shoppingList);

        $this->validateOnStart($event, $shoppingList, $validationGroups);
    }
}
