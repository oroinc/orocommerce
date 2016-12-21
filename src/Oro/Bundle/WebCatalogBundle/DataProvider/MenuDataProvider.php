<?php

namespace Oro\Bundle\WebCatalogBundle\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuDataProvider
{
    const IDENTIFIER = 'identifier';
    const LABEL = 'label';
    const URL = 'url';
    const CHILDREN = 'children';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var WebCatalogProvider
     */
    protected $webCatalogProvider;

    /**
     * @var ContentNodeTreeResolverInterface
     */
    protected $contentNodeTreeResolverFacade;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param ManagerRegistry $registry
     * @param WebCatalogProvider $webCatalogProvider
     * @param ContentNodeTreeResolverInterface $contentNodeTreeResolverFacade
     * @param LocalizationHelper $localizationHelper
     * @param RequestStack $requestStack
     */
    public function __construct(
        ManagerRegistry $registry,
        WebCatalogProvider $webCatalogProvider,
        ContentNodeTreeResolverInterface $contentNodeTreeResolverFacade,
        LocalizationHelper $localizationHelper,
        RequestStack $requestStack
    ) {
        $this->registry = $registry;
        $this->webCatalogProvider = $webCatalogProvider;
        $this->contentNodeTreeResolverFacade = $contentNodeTreeResolverFacade;
        $this->localizationHelper = $localizationHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $items = [];
        $request = $this->requestStack->getMasterRequest();

        if ($request && $scope = $request->attributes->get('_web_content_scope')) {
            $webCatalog = $this->webCatalogProvider->getWebCatalog();
            if ($webCatalog) {
                $rootNode = $this->getContentNodeRepository()->getRootNodeByWebCatalog($webCatalog);
                $resolvedNode = $this->contentNodeTreeResolverFacade->getResolvedContentNode($rootNode, $scope);

                $items[] = $this->prepareItemsData($resolvedNode);
            }
        }

        return $items;
    }

    /**
     * @param ResolvedContentNode $node
     * @return array
     */
    protected function prepareItemsData(ResolvedContentNode $node)
    {
        $result = [];

        $result[self::IDENTIFIER] = $node->getIdentifier();
        $result[self::LABEL] = (string)$this->localizationHelper->getLocalizedValue($node->getTitles());
        $result[self::URL] = (string)$this->localizationHelper
            ->getLocalizedValue($node->getResolvedContentVariant()->getLocalizedUrls());

        $result[self::CHILDREN] = [];
        foreach ($node->getChildNodes() as $child) {
            $result[self::CHILDREN][] = $this->prepareItemsData($child);
        }

        return $result;
    }

    /**
     * @return ContentNodeRepository
     */
    protected function getContentNodeRepository()
    {
        return $this->registry->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);
    }
}
