<?php

namespace Oro\Bundle\WebCatalogBundle\JsTree;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Bundle\WebCatalogBundle\Resolver\UniqueContentNodeSlugPrototypesResolver;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

/**
 * Handle Content node tree transitions.
 *
 * @method ContentNodeRepository getEntityRepository()
 */
class ContentNodeTreeHandler extends AbstractTreeHandler
{
    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var UniqueContentNodeSlugPrototypesResolver
     */
    private $uniqueSlugPrototypesResolver;

    /**
     * @var ResolveNodeSlugsMessageFactory
     */
    private $messageFactory;

    /**
     * @var bool
     */
    private $createRedirect = false;

    public function __construct(
        string $entityClass,
        ManagerRegistry $managerRegistry,
        LocalizationHelper $localizationHelper,
        MessageProducerInterface $messageProducer,
        ResolveNodeSlugsMessageFactory $messageFactory,
        UniqueContentNodeSlugPrototypesResolver $uniqueSlugPrototypesResolver
    ) {
        parent::__construct($entityClass, $managerRegistry);

        $this->localizationHelper = $localizationHelper;
        $this->messageProducer = $messageProducer;
        $this->messageFactory = $messageFactory;
        $this->uniqueSlugPrototypesResolver = $uniqueSlugPrototypesResolver;
    }

    /**
     * @param ContentNode $entity
     *
     * {@inheritdoc}
     */
    protected function formatEntity($entity)
    {
        $titleValue = $this->localizationHelper->getFirstNonEmptyLocalizedValue($entity->getTitles());
        return [
            'id' => $entity->getId(),
            'parent' => $entity->getParentNode() ? $entity->getParentNode()->getId() : null,
            'text' => $titleValue ? $titleValue->getString() : '',
            'state' => [
                'opened' => $entity->getParentNode() === null,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
        /** @var ContentNodeRepository $entityRepository */
        $entityRepository = $this->getEntityRepository();

        /** @var ContentNode $node */
        $node = $entityRepository->find($entityId);
        /** @var ContentNode $parentNode */
        $parentNode = $entityRepository->find($parentId);

        if (null === $parentNode) {
            $node->setParentNode(null);
        } else {
            if ($parentNode->getChildNodes()->contains($node)) {
                $parentNode->removeChildNode($node);
            }

            $parentNode->addChildNode($node);

            if ($position) {
                $children = array_values($parentNode->getChildNodes()->toArray());
                $entityRepository->persistAsNextSiblingOf($node, $children[$position - 1]);
            } else {
                $entityRepository->persistAsFirstChildOf($node, $parentNode);
            }
        }

        // Schedule slugs reorganization after node move
        $node->getSlugPrototypesWithRedirect()->setCreateRedirect($this->createRedirect);
        $this->uniqueSlugPrototypesResolver
            ->resolveSlugPrototypeUniqueness($node->getParentNode(), $node);
        $this->messageProducer->send(
            WebCatalogResolveContentNodeSlugsTopic::getName(),
            $this->messageFactory->createMessage($node)
        );

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function createTree($root = null, $includeRoot = true)
    {
        if (!$root) {
            return [];
        }

        return parent::createTree($root, $includeRoot);
    }

    /**
     * @param WebCatalog $webCatalog
     * @return ContentNode
     */
    public function getTreeRootByWebCatalog(WebCatalog $webCatalog)
    {
        return $this->getEntityRepository()->getRootNodeByWebCatalog($webCatalog);
    }

    /**
     * @param bool $createRedirect
     */
    public function setCreateRedirect($createRedirect)
    {
        $this->createRedirect = $createRedirect;
    }
}
