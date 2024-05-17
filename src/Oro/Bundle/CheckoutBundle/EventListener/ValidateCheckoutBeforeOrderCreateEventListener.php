<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates checkout line items to check if an order can be created.
 */
class ValidateCheckoutBeforeOrderCreateEventListener
{
    private ValidatorInterface $validator;

    private CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider;

    /** @var array<string|array<string>> */
    private array $validationGroups = [['Default', 'checkout_before_order_create%from_alias%']];

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

    public function onBeforeOrderCreate(ExtendableConditionEvent $event): void
    {
        $entity = $event->getContext()?->offsetGet('checkout');
        if (!$entity instanceof Checkout) {
            return;
        }

        $validationGroups = $this->validationGroupsProvider
            ->getValidationGroupsBySourceEntity($this->validationGroups, $entity->getSourceEntity());

        $violationList = $this->validator->validate($entity, null, $validationGroups);
        foreach ($violationList as $violation) {
            $event->addError($violation->getMessage(), $violation);
        }
    }
}
