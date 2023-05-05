<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether a content variant which is used as an identifier for a product collection
 * exists and an access to it is granted.
 */
class ValidateProductCollectionId implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ContentNodeProvider $contentNodeProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContentNodeProvider $contentNodeProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->contentNodeProvider = $contentNodeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        $contentVariantId = $context->getId();

        [$nodeId, $contentVariantType] = $this->getContentVariantInfo($contentVariantId);
        if (null === $nodeId) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }

        if (ProductCollectionContentVariantType::TYPE !== $contentVariantType) {
            throw new AccessDeniedException();
        }

        $contentVariantIds = $this->contentNodeProvider->getContentVariantIds([$nodeId]);
        if (($contentVariantIds[$nodeId] ?? null) !== $contentVariantId) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param int $contentVariantId
     *
     * @return array|null [node id, content variant type]
     */
    private function getContentVariantInfo(int $contentVariantId): ?array
    {
        $rows = $this->doctrineHelper
            ->createQueryBuilder(ContentVariant::class, 'e')
            ->select('IDENTITY(e.node) AS nodeId, e.type')
            ->where('e.id = :id')
            ->setParameter('id', $contentVariantId)
            ->getQuery()
            ->getArrayResult();

        if (!$rows) {
            return null;
        }

        $row = $rows[0];

        return [$row['nodeId'], $row['type']];
    }
}
