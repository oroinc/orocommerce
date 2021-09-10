<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierWithoutOrderByIterationStrategy;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * DataGrid mass action handler that move products between shopping lists.
 */
class MoveProductsMassActionHandler implements MassActionHandlerInterface
{
    private const FLUSH_BATCH_SIZE = 100;

    /** @var ManagerRegistry */
    private $registry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var RequestStack */
    private $requestStack;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    /** @var ShoppingListTotalManager */
    private $shoppingListTotalManager;

    public function __construct(
        ManagerRegistry $registry,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack,
        ShoppingListManager $shoppingListManager,
        ShoppingListTotalManager $shoppingListTotalManager
    ) {
        $this->registry = $registry;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->requestStack = $requestStack;
        $this->shoppingListManager = $shoppingListManager;
        $this->shoppingListTotalManager = $shoppingListTotalManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $request = $this->requestStack->getMainRequest();
        if ($request->getMethod() === 'POST') {
            $result = $this->doHandle($args);
        } else {
            $result = new MassActionResponse(
                false,
                sprintf('Request method "%s" is not supported', $request->getMethod())
            );
        }

        return $result;
    }

    private function doHandle(MassActionHandlerArgs $args): MassActionResponse
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
        $manager = $this->registry->getManagerForClass(LineItem::class);
        $updated = 0;
        $affectedShoppingLists = [];

        /** @var ResultRecordInterface[] $results */
        foreach ($results as $result) {
            $entity = $result->getRootEntity();
            if (!$entity) {
                // No entity in result record, it should be extracted from DB.
                $entity = $manager->getReference(LineItem::class, $result->getValue($entityIdentifiedField));
            }

            if (!$entity) {
                continue;
            }

            $origShoppingList = $entity->getShoppingList();
            if ($origShoppingList->getId() === $shoppingList->getId() || !$this->isEditAllowed($origShoppingList)) {
                continue;
            }

            $origShoppingList->removeLineItem($entity);
            $affectedShoppingLists[$origShoppingList->getId()] = $origShoppingList;
            $this->shoppingListManager->addLineItem($entity, $shoppingList, false);

            $updated++;
            if ($updated % self::FLUSH_BATCH_SIZE === 0) {
                $manager->flush();
            }
        }

        if ($updated % self::FLUSH_BATCH_SIZE > 0) {
            $manager->flush();
        }

        $this->recalculateTotals($manager, $affectedShoppingLists);

        return $this->getResponse($args->getMassAction(), $updated);
    }

    private function recalculateTotals(ObjectManager $manager, array $shoppingLists): void
    {
        foreach ($shoppingLists as $shoppingList) {
            $this->shoppingListTotalManager->recalculateTotals($shoppingList, false);
        }

        $manager->flush();
    }

    private function getTargetShoppingList(MassActionHandlerArgs $args): ?ShoppingList
    {
        $id = $args->getData()['shopping_list_id'] ?? null;
        if (!$id) {
            return null;
        }

        return $this->registry->getManagerForClass(ShoppingList::class)
            ->getRepository(ShoppingList::class)
            ->find($id);
    }

    private function isEditAllowed(?ShoppingList $shoppingList): bool
    {
        return $shoppingList && $this->authorizationChecker->isGranted('EDIT', $shoppingList);
    }

    /**
     * @param MassActionInterface $massAction
     * @param int $entitiesCount
     * @return MassActionResponse
     */
    private function getResponse(MassActionInterface $massAction, $entitiesCount = 0): MassActionResponse
    {
        $responseMessage = $massAction->getOptions()
            ->offsetGetByPath('[messages][success]', 'oro.shoppinglist.mass_actions.move_line_items.success_message');

        return new MassActionResponse(
            $entitiesCount > 0,
            $this->translator->trans($responseMessage, ['%count%' => $entitiesCount]),
            ['count' => $entitiesCount]
        );
    }

    private function getNoAccessResponse(): MassActionResponse
    {
        return new MassActionResponse(
            false,
            $this->translator->trans('oro.shoppinglist.mass_actions.move_line_items.no_edit_permission_message')
        );
    }

    /**
     * @throws LogicException
     */
    private function getEntityIdentifierField(MassActionHandlerArgs $args): string
    {
        $massAction = $args->getMassAction();
        $identifier = $massAction->getOptions()->offsetGet('data_identifier');
        if (!$identifier) {
            throw new LogicException(sprintf('Mass action "%s" must define identifier name', $massAction->getName()));
        }

        // if we ask identifier that's means that we have plain data in array
        // so we will just use column name without entity alias
        if (str_contains($identifier, '.')) {
            $parts = explode('.', $identifier);
            $identifier = end($parts);
        }

        return $identifier;
    }
}
