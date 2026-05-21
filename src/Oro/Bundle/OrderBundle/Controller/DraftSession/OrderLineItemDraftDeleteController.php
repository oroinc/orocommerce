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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
        $this->assertOrderDraftExists($order);

        $this->container->get(ManagerRegistry::class)
            ->getManagerForClass(OrderLineItem::class)
            ->remove($orderLineItem);

        $this->getOrderDraftManager()->saveToEntityDraft($orderLineItem);

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
            AuthorizationCheckerInterface::class,
            ManagerRegistry::class,
            OrderDraftManager::class,
        ];
    }
}
