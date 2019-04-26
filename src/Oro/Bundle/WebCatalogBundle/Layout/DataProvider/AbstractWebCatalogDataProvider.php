<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns nodes items
 */
abstract class AbstractWebCatalogDataProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param int|null $maxNodesNestedLevel
     * @return array
     */
    abstract public function getItems(int $maxNodesNestedLevel = null);

    /**
     * @return ContentNodeRepository
     */
    protected function getContentNodeRepository()
    {
        return $this->registry->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);
    }
}
