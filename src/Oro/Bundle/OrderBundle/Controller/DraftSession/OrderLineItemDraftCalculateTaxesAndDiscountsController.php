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
use Oro\Component\DraftSession\Util\EntityDraftUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
    #[ParamConverter(
        'order',
        class: Order::class,
        options: ['id' => 'orderId', 'repository_method' => 'getOrderWithRelations']
    )]
    #[ParamConverter(
        'orderLineItem',
        class: OrderLineItem::class,
        options: ['id' => 'orderLineItemId', 'repository_method' => 'findOrderLineItemWithRelations']
    )]
    #[AclAncestor('oro_order_update')]
    public function __invoke(
        Request $request,
        Order $order,
        OrderLineItem $orderLineItem
    ): JsonResponse {
        $this->assertOrderDraftExists($order);

        $order = $this->getOrderDraftManager()->loadFromEntityDraft($order);
        assert($order instanceof Order);

        $orderLineItem = EntityDraftUtils::getEntityFromDraft($orderLineItem);
        assert($orderLineItem instanceof OrderLineItem);

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
            AuthorizationCheckerInterface::class,
            OrderDraftManager::class,
            OrderLineItemTaxesAndDiscountsProvider::class,
            AppliedPromotionManager::class
        ];
    }
}
