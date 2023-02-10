<?php

namespace Oro\Bundle\OrderBundle\Acl\Voter;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Forbids the storefront order view page when showing suborders in order history is disabled.
 */
class SubOrderViewVoter extends Voter
{
    private ConfigProvider $multiShippingConfigProvider;
    private RequestStack $requestStack;

    public function __construct(
        ConfigProvider $multiShippingConfigProvider,
        RequestStack $requestStack
    ) {
        $this->multiShippingConfigProvider = $multiShippingConfigProvider;
        $this->requestStack = $requestStack;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return
            $attribute === BasicPermission::VIEW
            && $subject instanceof Order
            && $subject->getParent()
            && $this->requestStack->getCurrentRequest()->get('_route') === 'oro_order_frontend_view';
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->multiShippingConfigProvider->isShowSubordersInOrderHistoryEnabled();
    }
}
