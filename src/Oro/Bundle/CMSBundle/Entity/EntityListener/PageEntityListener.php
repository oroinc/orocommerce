<?php

namespace Oro\Bundle\CMSBundle\Entity\EntityListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Recalculate web catalog cache after landing page was removed
 */
class PageEntityListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var array */
    private $webCatalogIds;

    public function __construct(DoctrineHelper $doctrineHelper, MessageProducerInterface $messageProducer)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->messageProducer = $messageProducer;
    }

    public function preRemove(Page $entity, LifecycleEventArgs $args)
    {
        $repository = $this->doctrineHelper->getEntityRepository(ContentNode::class);
        $qb = $repository->createQueryBuilder('content_node');
        $qb->select('IDENTITY(content_node.webCatalog) as id');
        $qb->innerJoin('content_node.contentVariants', 'content_variant');
        $qb->where($qb->expr()->eq('content_variant.cms_page', ':cmsPage'));
        $qb->setParameter('cmsPage', $entity->getId());
        $qb->groupBy('content_node.webCatalog');

        $this->webCatalogIds = array_column($qb->getQuery()->getResult(), 'id');
    }

    public function postRemove(Page $entity, LifecycleEventArgs $args)
    {
        foreach ($this->webCatalogIds as $webCatalogId) {
            $this->messageProducer->send(
                WebCatalogCalculateCacheTopic::getName(),
                [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalogId]
            );
        }

        $this->webCatalogIds = [];
    }
}
