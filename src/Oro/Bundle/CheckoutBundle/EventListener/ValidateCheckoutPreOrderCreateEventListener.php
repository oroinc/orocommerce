<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates checkout line items to check if it is allowed to create an order.
 */
class ValidateCheckoutPreOrderCreateEventListener
{
    private CheckoutLineItemsProvider $checkoutLineItemsProvider;

    private ValidatorInterface $validator;

    private CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider;

    /** @var array<string|array<string>> */
    private array $validationGroups = [['Default', 'checkout_pre_order_create%from_alias%']];

    public function __construct(
        CheckoutLineItemsProvider $checkoutLineItemsProvider,
        ValidatorInterface $validator,
        CheckoutValidationGroupsBySourceEntityProvider $checkoutValidationGroupsBySourceEntityProvider
    ) {
        $this->checkoutLineItemsProvider = $checkoutLineItemsProvider;
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

    public function onPreOrderCreate(ExtendableConditionEvent $event): void
    {
        $entity = $event->getData()?->offsetGet('checkout');
        if (!$entity instanceof Checkout) {
            return;
        }

        $lineItems = $this->checkoutLineItemsProvider->getCheckoutLineItems($entity);
        if (!$lineItems->count()) {
            return;
        }

        $validationGroups = $this->validationGroupsProvider
            ->getValidationGroupsBySourceEntity($this->validationGroups, $entity->getSourceEntity());

        $violationList = $this->validator->validate($lineItems, null, $validationGroups);
        foreach ($violationList as $violation) {
            $event->addError($violation->getMessage(), $violation);
        }
    }
}
