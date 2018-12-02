<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access `order-line-items-grid-frontend` datagrid if user have no access to the order.
 */
class OrderLineItemsOrderObjectAccessListener
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ManagerRegistry $registry
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $registry)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->registry = $registry;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $order = $this->registry->getRepository(Order::class)->find(
            $event->getDatagrid()->getParameters()->get('order_id')
        );

        if (!$this->authorizationChecker->isGranted('VIEW', $order)) {
            throw new AccessDeniedException();
        }
    }
}
