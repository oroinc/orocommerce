<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The scorecard that provides the number of non-Cancelled RFQs, and that the current customer user has View access to
 */
class OpenRfqsScorecardProvider implements ScorecardInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly AclHelper $aclHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private array $excludedCustomerStatuses = []
    ) {
    }

    public function setExcludedCustomerStatuses(array $excludedCustomerStatuses): void
    {
        $this->excludedCustomerStatuses = $excludedCustomerStatuses;
    }

    #[\Override]
    public function getName(): string
    {
        return 'open_rfqs';
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.commerce.content_widget_type.scorecard.open_rfqs';
    }

    #[\Override]
    public function isVisible(): bool
    {
        return $this->authorizationChecker->isGranted(BasicPermission::VIEW, new Request());
    }

    #[\Override]
    public function getData(): ?string
    {
        $qb = $this->registry->getRepository(Request::class)->createQueryBuilder('r');
        $qb->select('COUNT(r.id)')
            ->andWhere($qb->expr()->notIn(
                "JSON_EXTRACT(r.serialized_data, 'customer_status')",
                ':excludedCustomerStatuses'
            ))
            ->setParameter(
                'excludedCustomerStatuses',
                ExtendHelper::mapToEnumOptionIds(Request::CUSTOMER_STATUS_CODE, $this->excludedCustomerStatuses),
                Connection::PARAM_STR_ARRAY
            );

        return $this->aclHelper->apply($qb)->getSingleScalarResult();
    }
}
