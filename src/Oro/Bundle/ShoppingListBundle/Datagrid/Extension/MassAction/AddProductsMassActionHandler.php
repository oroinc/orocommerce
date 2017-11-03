<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;

class AddProductsMassActionHandler implements MassActionHandlerInterface
{
    /** @var MessageGenerator */
    protected $messageGenerator;

    /**  @var ShoppingListLineItemHandler */
    protected $shoppingListLineItemHandler;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @param ShoppingListLineItemHandler $shoppingListLineItemHandler
     * @param MessageGenerator $messageGenerator
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        MessageGenerator $messageGenerator,
        ManagerRegistry $managerRegistry
    ) {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->messageGenerator = $messageGenerator;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $argsParser = new AddProductsMassActionArgsParser($args);
        $shoppingList = $argsParser->getShoppingList();
        $productIds = $argsParser->getProductIds();

        if (!$this->isAllowed($shoppingList, $productIds)) {
            return $this->generateResponse($args);
        }

        /** @var EntityManagerInterface $em */
        $em = $this->managerRegistry->getManagerForClass(ShoppingList::class);
        $em->beginTransaction();

        try {
            if (!$shoppingList->getId()) {
                $em->persist($shoppingList);
                $em->flush();
            }

            $addedCnt = $this->shoppingListLineItemHandler->createForShoppingList(
                $shoppingList,
                $productIds,
                $argsParser->getUnitsAndQuantities()
            );

            $em->commit();

            return $this->generateResponse($args, $addedCnt, $shoppingList->getId());
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int $entitiesCount
     * @param int|null $shoppingListId
     *
     * @return MassActionResponse
     */
    protected function generateResponse(MassActionHandlerArgs $args, $entitiesCount = 0, $shoppingListId = null)
    {
        $transChoiceKey = $args->getMassAction()->getOptions()->offsetGetByPath(
            '[messages][success]',
            'oro.shoppinglist.actions.add_success_message'
        );

        return new MassActionResponse(
            $entitiesCount > 0 && $shoppingListId,
            $this->messageGenerator->getSuccessMessage($shoppingListId, $entitiesCount, $transChoiceKey),
            ['count' => $entitiesCount]
        );
    }

    /**
     * @param ShoppingList|null $shoppingList
     * @param array $productIds
     * @return bool
     */
    private function isAllowed($shoppingList, array $productIds): bool
    {
        return $shoppingList && $productIds && $this->shoppingListLineItemHandler->isAllowed();
    }
}
