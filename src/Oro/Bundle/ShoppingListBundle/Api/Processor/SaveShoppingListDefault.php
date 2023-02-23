<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListStorage;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Analyzes data collected be HandleShoppingListDefault processor
 * and change the default shopping list for the current authenticated customer user.
 * @see \Oro\Bundle\ShoppingListBundle\Api\Processor\HandleShoppingListDefault
 */
class SaveShoppingListDefault implements ProcessorInterface
{
    private TokenAccessorInterface $tokenAccessor;
    private CurrentShoppingListStorage $currentShoppingListStorage;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        CurrentShoppingListStorage $currentShoppingListStorage
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->currentShoppingListStorage = $currentShoppingListStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        /** @var array|null $submittedValues */
        $submittedValues = $context->getSharedData()->get(HandleShoppingListDefault::SUBMITTED_DEFAULT_VALUES);
        if (!$submittedValues) {
            return;
        }

        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof CustomerUser) {
            return;
        }

        $submittedData = $this->getSubmittedData($context, $submittedValues);
        if (!$submittedData) {
            return;
        }

        $this->setCurrentShoppingListId($user->getId(), $submittedData);
    }

    /**
     * @param FormContext $context
     * @param array       $submittedValues [shopping list entity hash => submitted default value, ...]
     *
     * @return array [shopping list id => submitted default value, ...]
     */
    private function getSubmittedData(FormContext $context, array $submittedValues): array
    {
        $result = [];
        $entities = $context->getAllEntities();
        foreach ($entities as $entity) {
            if ($entity instanceof ShoppingList) {
                $entityHash = spl_object_hash($entity);
                foreach ($submittedValues as $hash => $value) {
                    if ($entityHash === $hash) {
                        $result[$entity->getId()] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param int   $customerUserId
     * @param array $submittedData [shopping list id => submitted default value, ...]
     */
    private function setCurrentShoppingListId(int $customerUserId, array $submittedData): void
    {
        $newCurrentShoppingListId = $this->getCurrentShoppingListId($submittedData);
        if (null !== $newCurrentShoppingListId) {
            $this->currentShoppingListStorage->set($customerUserId, $newCurrentShoppingListId);
        } else {
            $currentShoppingListId = $this->currentShoppingListStorage->get($customerUserId);
            if (null !== $currentShoppingListId) {
                foreach ($submittedData as $shoppingListId => $value) {
                    if (!$value && $currentShoppingListId === $shoppingListId) {
                        $this->currentShoppingListStorage->set($customerUserId, null);
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param array $submittedData [shopping list id => submitted default value, ...]
     *
     * @return int|null
     */
    private function getCurrentShoppingListId(array $submittedData): ?int
    {
        foreach ($submittedData as $shoppingListId => $value) {
            if ($value) {
                return $shoppingListId;
            }
        }

        return null;
    }
}
