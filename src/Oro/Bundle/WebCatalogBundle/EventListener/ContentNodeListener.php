<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Component\DependencyInjection\ServiceLink;

class ContentNodeListener
{
    const FIELD_TITLES = 'titles';

    /**
     * @var ContentNodeMaterializedPathModifier
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;

    /**
     * @var ServiceLink
     */
    protected $slugGeneratorLink;

    /**
     * @param ContentNodeMaterializedPathModifier $modifier
     * @param ExtraActionEntityStorageInterface $storage
     * @param ServiceLink $slugGenerator
     */
    public function __construct(
        ContentNodeMaterializedPathModifier $modifier,
        ExtraActionEntityStorageInterface $storage,
        ServiceLink $slugGenerator
    ) {
        $this->modifier = $modifier;
        $this->storage = $storage;
        $this->slugGeneratorLink = $slugGenerator;
    }

    /**
     * @param ContentNode $contentNode
     */
    public function postPersist(ContentNode $contentNode)
    {
        $contentNode = $this->modifier->calculateMaterializedPath($contentNode);
        $this->storage->scheduleForExtraInsert($contentNode);
    }
    
    /**
     * @param ContentNode $contentNode
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(ContentNode $contentNode, PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();
        
        if (!empty($changeSet[ContentNode::FIELD_PARENT_NODE])) {
            $this->getSlugGeneratorLink()->generate($contentNode);
            $this->modifier->calculateMaterializedPath($contentNode);
            $childNodes = $this->modifier->calculateChildrenMaterializedPath($contentNode);

            $this->storage->scheduleForExtraInsert($contentNode);
            foreach ($childNodes as $childNode) {
                $this->storage->scheduleForExtraInsert($childNode);
            }
        }
    }

    /**
     * @return SlugGenerator
     */
    protected function getSlugGeneratorLink()
    {
        /** @var SlugGenerator $slugGenerator */
        $slugGenerator = $this->slugGeneratorLink->getService();
        return $slugGenerator;
    }
}
