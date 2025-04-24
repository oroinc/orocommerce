<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The scorecard that provides the number of shopping lists that the current customer user has View access to
 */
class ShoppingListsScorecardProvider implements ScorecardInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly AclHelper $aclHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return 'shopping_lists';
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.commerce.content_widget_type.scorecard.shopping_lists';
    }

    #[\Override]
    public function isVisible(): bool
    {
        return $this->authorizationChecker->isGranted(BasicPermission::VIEW, new ShoppingList());
    }

    #[\Override]
    public function getData(): ?string
    {
        $qb = $this->registry->getRepository(ShoppingList::class)->createQueryBuilder('sh');

        return $this->aclHelper->apply($qb->select('COUNT(sh.id)'))->getSingleScalarResult();
    }
}
