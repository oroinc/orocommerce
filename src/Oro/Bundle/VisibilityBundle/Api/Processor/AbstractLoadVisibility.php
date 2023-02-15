<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\VisibilityBundle\Api\VisibilityIdHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The base processor to loads a visibility entity from the database by its ID.
 */
abstract class AbstractLoadVisibility implements ProcessorInterface
{
    private const WEBSITE_ID_PROPERTY_PATH = 'scope.website.id';

    private DoctrineHelper $doctrineHelper;
    private AclHelper $aclHelper;
    private WebsiteManager $websiteManager;
    private VisibilityIdHelper $visibilityIdHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        WebsiteManager $websiteManager,
        VisibilityIdHelper $visibilityIdHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
        $this->websiteManager = $websiteManager;
        $this->visibilityIdHelper = $visibilityIdHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // the entity is already loaded
            return;
        }

        $visibility = null;
        $visibilityId = $this->visibilityIdHelper->decodeVisibilityId(
            $context->getId(),
            $context->getConfig()->getField('id')
        );
        if (null !== $visibilityId) {
            $visibility = $this->loadVisibility($visibilityId);
        }

        $context->setResult($visibility);
    }

    /**
     * @param array $visibilityId [property path => value, ...]
     *
     * @return object|null
     */
    private function loadVisibility(array $visibilityId): ?object
    {
        $scopeId = $this->getScopeId($visibilityId);
        if (null === $scopeId) {
            return null;
        }

        // try to load an entity by ACL protected query
        $qb = $this->doctrineHelper->createQueryBuilder($this->getVisibilityEntityClass(), 'e')
            ->where('e.' . $this->getVisibilityAssociationName() . ' = :entityId')
            ->andWhere('e.scope = :scopeId')
            ->setParameter('entityId', $this->getId($visibilityId, $this->getVisibilityAssociationIdPropertyPath()))
            ->setParameter('scopeId', $scopeId);
        $visibility = $this->aclHelper->apply($qb)->getOneOrNullResult();
        if (null === $visibility) {
            // use a query without ACL protection to check if an entity exists in DB
            $qb->select('e.id');
            $notAclProtectedData = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
            if ($notAclProtectedData) {
                throw new AccessDeniedException('No access to the entity.');
            }
        }

        return $visibility;
    }

    /**
     * @param array  $visibilityId [property path => value, ...]
     * @param string $propertyPath
     *
     * @return int
     */
    protected function getId(array $visibilityId, string $propertyPath): int
    {
        return $this->visibilityIdHelper->getId($visibilityId, $propertyPath);
    }

    /**
     * @param array $visibilityId [property path => value, ...]
     *
     * @return Website
     */
    protected function getWebsite(array $visibilityId): Website
    {
        if (!\array_key_exists(self::WEBSITE_ID_PROPERTY_PATH, $visibilityId)) {
            return $this->websiteManager->getDefaultWebsite();
        }

        return $this->getReference(Website::class, $this->getId($visibilityId, self::WEBSITE_ID_PROPERTY_PATH));
    }

    protected function getReference(string $entityClass, int $entityId): object
    {
        return $this->doctrineHelper->getEntityReference($entityClass, $entityId);
    }

    abstract protected function getVisibilityEntityClass(): string;

    abstract protected function getVisibilityAssociationName(): string;

    protected function getVisibilityAssociationIdPropertyPath(): string
    {
        return $this->getVisibilityAssociationName() . '.id';
    }

    /**
     * @param int[] $visibilityId [property path => value, ...]
     *
     * @return int|null
     */
    abstract protected function getScopeId(array $visibilityId): ?int;
}
