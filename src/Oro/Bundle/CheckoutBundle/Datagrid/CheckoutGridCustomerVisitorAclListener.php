<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Applies ACL rule to filter checkouts by current customer visitor.
 * The applyVisitorAcl method in this class should be converted to AccessRule in BB-15019.
 */
class CheckoutGridCustomerVisitorAclListener
{
    /** @var FeatureChecker */
    private $featureChecker;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param FeatureChecker        $featureChecker
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(FeatureChecker $featureChecker, TokenStorageInterface $tokenStorage)
    {
        $this->featureChecker = $featureChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken) {
            return;
        }

        if (!$this->featureChecker->isFeatureEnabled('guest_checkout')) {
            throw new AccessDeniedException('The guest checkout is disabled.');
        }

        $visitor = $token->getVisitor();
        if (null !== $visitor) {
            $this->applyVisitorAcl($event->getConfig(), $visitor->getId());
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param int                   $visitorId
     */
    private function applyVisitorAcl(DatagridConfiguration $config, $visitorId)
    {
        $query = $config->getOrmQuery();
        $query
            ->addInnerJoin('checkout.source', 'source')
            ->addAndWhere(sprintf(
                'EXISTS(SELECT 1 FROM %s visitor WHERE visitor.id = %d'
                . ' AND source.shoppingList MEMBER OF visitor.shoppingLists)',
                CustomerVisitor::class,
                $visitorId
            ));
    }
}
