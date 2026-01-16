<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierWithoutOrderByIterationStrategy;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * DataGrid mass action handler that handle save for later or remove from saved for later items in shopping list.
 */
class SaveForLaterMassActionHandler implements MassActionHandlerInterface
{
    private const int FLUSH_BATCH_SIZE = 100;

    /**
     * Indicates whether the line item should be moved to the SavedForLaterList.
     * If true, the item will be moved to the SavedForLaterList.
     * If false, the item will be moved back to the main shopping list.
     */
    private bool $saveForLaterFlag = true;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly TranslatorInterface $translator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ShoppingListManager $shoppingListManager,
        private readonly ShoppingListTotalManager $shoppingListTotalManager
    ) {
    }

    public function setSaveForLaterFlag(bool $saveForLaterFlag = true): void
    {
        $this->saveForLaterFlag = $saveForLaterFlag;
    }

    #[\Override]
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $shoppingList = $this->getTargetShoppingList($args);
        if (!$this->isEditAllowed($shoppingList)) {
            return $this->getNoAccessResponse();
        }

        $results = new IterableResult($args->getResults()->getSource());
        $results->setIterationStrategy(new IdentifierWithoutOrderByIterationStrategy());
        $results->setBufferSize(self::FLUSH_BATCH_SIZE);

        $entityIdentifiedField = $this->getEntityIdentifierField($args);
        /** @var EntityManager $manager */
        $manager = $this->registry->getManager();
        $updated = 0;

        /** @var ResultRecordInterface[] $results */
        foreach ($results as $result) {
            $id = $result->getValue($entityIdentifiedField);
            $entity = $result->getRootEntity() ?? $manager->getReference(LineItem::class, $id);

            if (!$entity) {
                continue;
            }

            if ($this->saveForLaterFlag) {
                $entity->setSavedForLaterList($shoppingList);
            } else {
                $entity->setShoppingList($shoppingList);
            }

            $this->shoppingListManager->addLineItem($entity, $shoppingList, false);

            $updated++;
            if ($updated % self::FLUSH_BATCH_SIZE === 0) {
                $manager->flush();
            }
        }

        if ($updated % self::FLUSH_BATCH_SIZE > 0) {
            $manager->flush();
        }

        if ($updated > 0) {
            $this->shoppingListTotalManager->recalculateTotals($shoppingList, false);
            $manager->flush();
        }

        return $this->getResponse($args->getMassAction(), $updated);
    }

    private function getTargetShoppingList(MassActionHandlerArgs $args): ?ShoppingList
    {
        $id = $args->getData()[$args->getDatagrid()->getName()]['shopping_list_id'] ?? null;
        if (!$id) {
            return null;
        }

        return $this->registry->getRepository(ShoppingList::class)->find($id);
    }

    private function isEditAllowed(?ShoppingList $shoppingList): bool
    {
        return $shoppingList && $this->authorizationChecker->isGranted('EDIT', $shoppingList);
    }

    private function getResponse(MassActionInterface $massAction, int $entitiesCount = 0): MassActionResponse
    {
        $responseMessage = $massAction->getOptions()
            ->offsetGetByPath('[messages][success]', 'Mass action performed.');

        return new MassActionResponse(
            $entitiesCount > 0,
            $this->translator->trans($responseMessage, ['%count%' => $entitiesCount]),
            ['count' => $entitiesCount]
        );
    }

    private function getEntityIdentifierField(MassActionHandlerArgs $args): string
    {
        $massAction = $args->getMassAction();
        $identifier = $massAction->getOptions()->offsetGetOr('data_identifier');
        if (!$identifier) {
            throw new LogicException(\sprintf('Mass action "%s" must define identifier name.', $massAction->getName()));
        }

        // if we ask identifier that's means that we have plain data in array
        // so we will just use column name without entity alias
        if (\str_contains($identifier, '.')) {
            $parts = \explode('.', $identifier);
            $identifier = \end($parts);
        }

        return $identifier;
    }

    private function getNoAccessResponse(): MassActionResponse
    {
        return new MassActionResponse(
            false,
            $this->translator->trans('oro.shoppinglist.mass_actions.save_for_later.no_edit_permission_message')
        );
    }
}
