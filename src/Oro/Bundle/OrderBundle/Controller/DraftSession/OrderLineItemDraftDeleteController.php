<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to delete a draft order line item.
 */
final class OrderLineItemDraftDeleteController extends AbstractController
{
    use DraftSessionControllerTrait;

    #[Route(
        path: '/{orderId}/line-item/delete/{orderLineItemId}/{orderDraftSessionUuid}',
        name: 'oro_order_line_item_draft_delete',
        requirements: [
            'orderId' => '\d+',
            'orderLineItemId' => '\d+',
            'orderDraftSessionUuid' => '%oro_order.draft_session.uuid_regex%',
        ],
        methods: ['DELETE']
    )]
    #[AclAncestor('oro_order_update')]
    public function __invoke(
        #[MapEntity(expr: 'repository.getOrderWithRelations(orderId)')]
        ?Order $order,
        #[MapEntity(expr: 'repository.findOrderLineItemWithRelations(orderLineItemId)')]
        OrderLineItem $orderLineItem
    ): Response {
        $orderDraft = $this->getOrderDraft();
        $orderLineItemDraft = $this->findOrCreateOrderLineItemDraft($orderDraft, $orderLineItem);

        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->container->get(ManagerRegistry::class);
        $entityManager = $doctrine->getManagerForClass(OrderLineItem::class);

        // Scenario 1: Existing draft (modified source item in current session)
        // Action: Mark the draft as deleted
        if ($orderLineItemDraft !== $orderLineItem) {
            $orderLineItemDraft->setDraftDelete(true);
            $entityManager->persist($orderLineItemDraft);
        } else {
            // Scenario 2: New draft (added in current session, no source)
            // Action: Physical delete - it doesn't exist in the original order
            $entityManager->remove($orderLineItemDraft);
        }

        $entityManager->flush();

        return new JsonResponse([
            'successful' => true,
            'widget' => [
                'trigger' => [
                    [
                        'eventBroker' => 'mediator',
                        'name' => 'datagrid:doRefresh:orderDraftGrid:order-line-items-edit-grid'
                    ],
                ],
            ],
        ]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            ManagerRegistry::class,
            OrderDraftManager::class,
        ];
    }
}
