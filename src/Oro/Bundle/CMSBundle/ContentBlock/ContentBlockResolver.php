<?php

namespace Oro\Bundle\CMSBundle\ContentBlock;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Provide `Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView\ContentBlockView`
 * based on most suitable Content Variant for Content Block.
 */
class ContentBlockResolver
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ContentBlock $contentBlock
     * @param ScopeCriteria $criteria
     * @return null|ContentBlockView
     */
    public function getContentBlockViewByCriteria(ContentBlock $contentBlock, ScopeCriteria $criteria)
    {
        if (!$this->isContentBlockVisible($contentBlock, $criteria)) {
            return null;
        }

        $repo = $this->registry->getManagerForClass(TextContentVariant::class)
            ->getRepository(TextContentVariant::class);

        $mostSuitableContentVariant = $repo->getMatchingVariantForBlockByCriteria($contentBlock, $criteria);
        if (!$mostSuitableContentVariant) {
            $mostSuitableContentVariant = $repo->getDefaultContentVariantForContentBlock($contentBlock);
            if (null === $mostSuitableContentVariant) {
                throw new \RuntimeException('Default content variant should be defined.');
            }
        }

        return new ContentBlockView(
            $contentBlock->getAlias(),
            $contentBlock->getTitles(),
            $contentBlock->isEnabled(),
            (string)$mostSuitableContentVariant->getContent(),
            (string)$mostSuitableContentVariant->getContentStyle()
        );
    }

    /**
     * @param ContentBlock $contentBlock
     * @param ScopeCriteria $criteria
     * @return bool true if ContentBlock enabled and has at least one supported scope
     */
    private function isContentBlockVisible(ContentBlock $contentBlock, ScopeCriteria $criteria)
    {
        if (!$contentBlock->isEnabled()) {
            return false;
        }

        if ($contentBlock->getScopes()->isEmpty()) {
            return true;
        }

        $repo = $this->registry->getManagerForClass(ContentBlock::class)
            ->getRepository(ContentBlock::class);

        return $repo->getMostSuitableScope($contentBlock, $criteria) !== null;
    }
}
