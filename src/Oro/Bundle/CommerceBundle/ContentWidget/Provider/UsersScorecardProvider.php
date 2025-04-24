<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The scorecard that provides the number of customer users in the current customer
 */
class UsersScorecardProvider implements ScorecardInterface
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
        return 'users';
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.commerce.content_widget_type.scorecard.users';
    }

    #[\Override]
    public function isVisible(): bool
    {
        return $this->authorizationChecker->isGranted(BasicPermission::VIEW, new CustomerUser());
    }

    #[\Override]
    public function getData(): ?string
    {
        $qb = $this->registry->getRepository(CustomerUser::class)->createQueryBuilder('cu');

        return $this->aclHelper->apply($qb->select('COUNT(cu.id)'))->getSingleScalarResult();
    }
}
