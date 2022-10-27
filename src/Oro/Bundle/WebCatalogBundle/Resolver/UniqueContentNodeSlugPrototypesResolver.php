<?php

namespace Oro\Bundle\WebCatalogBundle\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Get unique slug prototypes.
 */
class UniqueContentNodeSlugPrototypesResolver
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function resolveSlugPrototypeUniqueness(?ContentNode $parentNode, ContentNode $contentNode)
    {
        foreach ($this->getUniqueSlugPrototypes($parentNode, $contentNode) as [$removed, $new]) {
            $contentNode->removeSlugPrototype($removed);
            $contentNode->addSlugPrototype($new);
        }
    }

    /**
     * @param ContentNode|null $parentNode
     * @param ContentNode $contentNode
     * @return \Generator
     */
    private function getUniqueSlugPrototypes(?ContentNode $parentNode, ContentNode $contentNode)
    {
        $persistedPrototypeStrings = $this->getPersistedSlugPrototypeStrings($parentNode, $contentNode);

        foreach ($contentNode->getSlugPrototypes() as $slugPrototype) {
            $originalString = mb_strtolower($slugPrototype->getString());

            $idx = 1;
            $uniqueSlugPrototypeString = $originalString;
            while (in_array($uniqueSlugPrototypeString, $persistedPrototypeStrings, true)) {
                $uniqueSlugPrototypeString = $originalString . '-' . $idx;
                $idx++;
            }

            if ($uniqueSlugPrototypeString !== $originalString) {
                $newSlugPrototype = new LocalizedFallbackValue();
                $newSlugPrototype->setString($uniqueSlugPrototypeString);
                $newSlugPrototype->setFallback($slugPrototype->getFallback());
                $newSlugPrototype->setLocalization($slugPrototype->getLocalization());

                yield [$slugPrototype, $newSlugPrototype];
            }
        }
    }

    private function getPersistedSlugPrototypeStrings(?ContentNode $parentNode, ContentNode $currentNode): array
    {
        $repository = $this->registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);

        return $repository->getSlugPrototypesByParent(
            $parentNode,
            $currentNode->getId() ? $currentNode : null
        );
    }
}
