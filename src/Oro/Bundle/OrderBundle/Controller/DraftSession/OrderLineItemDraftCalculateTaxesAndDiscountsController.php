<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderLineItemTaxesAndDiscountsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to get taxes and discounts breakdown for given order line item draft.
 */
final class OrderLineItemDraftCalculateTaxesAndDiscountsController extends AbstractController
{
    use DraftSessionControllerTrait;

    #[Route(
        path: '/{orderId}/line-item/calculate-taxes-discounts/{orderLineItemId}/{orderDraftSessionUuid}',
        name: 'oro_order_line_item_draft_calculate_taxes_and_discounts',
        requirements: [
            'orderId' => '\d+',
            'orderLineItemId' => '\d+',
            'orderDraftSessionUuid' => '%oro_order.draft_session.uuid_regex%',
        ],
        methods: ['POST']
    )]
    #[AclAncestor('oro_order_update')]
    public function __invoke(
        Request $request,
        #[MapEntity(expr: 'repository.getOrderWithRelations(orderId)')]
        ?Order $order,
        #[MapEntity(expr: 'repository.findOrderLineItemWithRelations(orderLineItemId)')]
        OrderLineItem $orderLineItem
    ): JsonResponse {
        $orderDraft = $this->getOrderDraft();
        $order = $this->syncFromOrderDraft($orderDraft, $order);
        $orderLineItem = $this->getOrderLineItemDraftSource($orderLineItem);

        $form = $this->createForm(OrderLineItemDraftType::class, $orderLineItem);
        $form->handleRequest($request);

        $this->container->get(AppliedPromotionManager::class)->createAppliedPromotions($order);

        /** @var OrderLineItemTaxesAndDiscountsProvider $taxesAndDiscountsProvider */
        $taxesAndDiscountsProvider = $this->container->get(OrderLineItemTaxesAndDiscountsProvider::class);

        $responseData = ['success' => true];

        $responseData['lineItemTaxesHtml'] = $this->renderView(
            '@OroOrder/Order/orderLineItemDraftTaxes.html.twig',
            [
                'orderLineItem' => $orderLineItem,
                'lineItemTaxes' => $taxesAndDiscountsProvider->getLineItemTaxes($orderLineItem),
            ]
        );

        $responseData['lineItemDiscountsHtml'] = $this->renderView(
            '@OroOrder/Order/orderLineItemDraftDiscounts.html.twig',
            [
                'orderLineItem' => $orderLineItem,
                'lineItemDiscounts' => $taxesAndDiscountsProvider->getLineItemDiscounts($orderLineItem),
            ]
        );

        return new JsonResponse($responseData);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            OrderDraftManager::class,
            OrderLineItemTaxesAndDiscountsProvider::class,
            AppliedPromotionManager::class
        ];
    }
}
