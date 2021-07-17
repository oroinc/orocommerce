<?php

namespace Oro\Bundle\WebCatalogBundle\Form\DataTransformer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms ContentNode entity to its id before saving to db
 */
class NavigationRootOptionTransformer implements DataTransformerInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value && !$value instanceof ContentNode) {
            $value = $this->doctrineHelper->getEntityRepository(ContentNode::class)->find($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($value instanceof ContentNode) {
            return $value->getId();
        }

        return $value;
    }
}
