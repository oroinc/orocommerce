<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;

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
     * @return array
     */
    abstract public function getItems();

    /**
     * @return ContentNodeRepository
     */
    protected function getContentNodeRepository()
    {
        return $this->registry->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);
    }
}
