<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Layout data provider that helps to build main navigation menu on the front store
 */
class MenuDataProvider extends AbstractWebCatalogDataProvider
{
    const IDENTIFIER = 'identifier';
    const LABEL = 'label';
    const URL = 'url';
    const CHILDREN = 'children';

    /**
     * @var WebCatalogProvider
     */
    protected $webCatalogProvider;

    /**
     * @var ContentNodeTreeResolverInterface
     */
    protected $contentNodeTreeResolverFacade;

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
        $request = $this->requestStack->getCurrentRequest();

        $rootItem = [];
        if ($request && $scope = $request->attributes->get('_web_content_scope')) {
            $rootNode = $this->webCatalogProvider->getNavigationRoot();
            if (!$rootNode) {
                $webCatalog = $this->webCatalogProvider->getWebCatalog();
                if ($webCatalog) {
                    $rootNode = $this->getContentNodeRepository()->getRootNodeByWebCatalog($webCatalog);
                }
            }

            if ($rootNode) {
                $resolvedNode = $this->contentNodeTreeResolverFacade->getResolvedContentNode($rootNode, $scope);

                if ($resolvedNode) {
                    $rootItem = $this->prepareItemsData($resolvedNode);
                }
            }
        }

        if (array_key_exists(self::CHILDREN, $rootItem)) {
            return $rootItem[self::CHILDREN];
        }

        return [];
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
}
