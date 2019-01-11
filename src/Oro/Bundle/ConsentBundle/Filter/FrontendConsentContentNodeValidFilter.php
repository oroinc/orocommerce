<?php

namespace Oro\Bundle\ConsentBundle\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Validator\ConsentContentNodeValidator;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Psr\Log\LoggerInterface;

/**
 * Allow to filter consent that has valid content node variant
 * (Content node variant will be resolved by request params)
 */
class FrontendConsentContentNodeValidFilter implements ConsentFilterInterface
{
    const NAME = 'frontend_consent_content_node_valid_filter';

    /**
     * @var WebCatalogProvider
     */
    private $webCatalogProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @var ConsentContentNodeValidator
     */
    private $contentNodeValidator;

    /**
     * @var ConsentAcceptanceProvider
     */
    private $consentAcceptanceProvider;

    /**
     * @param WebCatalogProvider $webCatalogProvider
     * @param LoggerInterface $logger
     * @param WebsiteManager $websiteManager
     * @param FrontendHelper $frontendHelper
     * @param ConsentContentNodeValidator $contentNodeValidator
     * @param ConsentAcceptanceProvider $consentAcceptanceProvider
     */
    public function __construct(
        WebCatalogProvider $webCatalogProvider,
        LoggerInterface $logger,
        WebsiteManager $websiteManager,
        FrontendHelper $frontendHelper,
        ConsentContentNodeValidator $contentNodeValidator,
        ConsentAcceptanceProvider $consentAcceptanceProvider
    ) {
        $this->webCatalogProvider = $webCatalogProvider;
        $this->logger = $logger;
        $this->frontendHelper = $frontendHelper;
        $this->websiteManager = $websiteManager;
        $this->contentNodeValidator = $contentNodeValidator;
        $this->consentAcceptanceProvider = $consentAcceptanceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isConsentPassedFilter(Consent $consent, array $params = [])
    {
        /**
         * Consent without content node is valid by default
         */
        $contentNode = $consent->getContentNode();
        if (!$contentNode instanceof ContentNode) {
            return true;
        }

        if (!$this->frontendHelper->isFrontendRequest()) {
            return true;
        }

        /**
         * ConsentAcceptance contains fully resolved data about cmsPage,
         * there is no need to check content node if it exists
         */
        $consentAcceptance = $this->consentAcceptanceProvider->getCustomerConsentAcceptanceByConsentId(
            $consent->getId()
        );
        if ($consentAcceptance instanceof ConsentAcceptance) {
            return true;
        }

        return $this->isValid($consent, $params);
    }

    /**
     * @param Consent $consent
     * @param array   $params
     *
     * @return bool
     */
    private function isValid(Consent $consent, array $params = [])
    {
        $contentNode = $consent->getContentNode();
        $currentWebCatalog = $this->webCatalogProvider->getWebCatalog();

        if ($this->isLogErrorsEnabled($params) &&
            ($currentWebCatalog === null || $currentWebCatalog !== $contentNode->getWebCatalog())) {
            $this->logger->error(
                sprintf(
                    "Consent with id '%d' point to the WebCatalog that doesn't use in the website with id '%d'!",
                    $consent->getId(),
                    $this->websiteManager->getCurrentWebsite()->getId()
                )
            );

            return false;
        }

        return $this->contentNodeValidator->isValid(
            $contentNode,
            $consent,
            $this->isLogErrorsEnabled($params)
        );
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function isLogErrorsEnabled(array $params = [])
    {
        return $params[self::LOG_ERRORS_PARAMETER] ?? true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
