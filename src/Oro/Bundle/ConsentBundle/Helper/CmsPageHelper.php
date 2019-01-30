<?php

namespace Oro\Bundle\ConsentBundle\Helper;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProviderInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Use it to get CMS Page object for given Consent and ConsentAcceptance if present
 */
class CmsPageHelper
{
    /** @var ContentNodeTreeResolverInterface */
    protected $contentNodeTreeResolver;

    /** @var ConsentContextProviderInterface */
    protected $consentContextProvider;

    /**
     * @param ContentNodeTreeResolverInterface $contentNodeTreeResolver
     * @param ConsentContextProviderInterface $consentContextProvider
     */
    public function __construct(
        ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        ConsentContextProviderInterface $consentContextProvider
    ) {
        $this->contentNodeTreeResolver = $contentNodeTreeResolver;
        $this->consentContextProvider = $consentContextProvider;
    }

    /**
     * @param Consent $consent
     * @param ConsentAcceptance|null $consentAcceptance
     *
     * @return null|Page
     */
    public function getCmsPage(Consent $consent, ConsentAcceptance $consentAcceptance = null)
    {
        $cmsPage = null;

        if ($consentAcceptance instanceof ConsentAcceptance) {
            $cmsPage = $consentAcceptance->getLandingPage();
        } else {
            $contentVariant = $this->getResolvedContentVariant($consent);
            if ($contentVariant instanceof ResolvedContentVariant) {
                /**
                 * Data by key 'cms_page' contains CmsPage in case
                 * if ResolvedContentVariant contains content type CmsPageContentVariantType::TYPE
                 */
                $cmsPage = $contentVariant->cms_page;
            }
        }

        if ($cmsPage instanceof Page) {
            return $cmsPage;
        }

        return null;
    }

    /**
     * @param Consent $consent
     *
     * @return null|ResolvedContentVariant
     */
    protected function getResolvedContentVariant(Consent $consent)
    {
        $contentNode = $consent->getContentNode();
        if (!$contentNode instanceof ContentNode) {
            return null;
        }

        $scope = $this->consentContextProvider->getScope();
        if ($scope instanceof Scope) {
            $resolvedNode = $this->contentNodeTreeResolver->getResolvedContentNode($contentNode, $scope);

            if (!$resolvedNode instanceof ResolvedContentNode) {
                return null;
            }

            $resolvedContentVariant = $resolvedNode->getResolvedContentVariant();
            if ($resolvedContentVariant->getType() === CmsPageContentVariantType::TYPE) {
                return $resolvedContentVariant;
            }
        }

        return null;
    }
}
