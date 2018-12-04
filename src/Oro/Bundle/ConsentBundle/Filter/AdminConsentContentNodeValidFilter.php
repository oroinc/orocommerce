<?php

namespace Oro\Bundle\ConsentBundle\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Validator\ConsentContentNodeValidator;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Allows to filter consent that has valid default content node variant
 */
class AdminConsentContentNodeValidFilter implements ConsentFilterInterface
{
    const NAME = 'admin_consent_content_node_valid_filter';

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
     * @param FrontendHelper $frontendHelper
     * @param ConsentContentNodeValidator $contentNodeValidator
     * @param ConsentAcceptanceProvider $consentAcceptanceProvider
     */
    public function __construct(
        FrontendHelper $frontendHelper,
        ConsentContentNodeValidator $contentNodeValidator,
        ConsentAcceptanceProvider $consentAcceptanceProvider
    ) {
        $this->frontendHelper = $frontendHelper;
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

        /**
         * This filter is applicable only on backend side
         */
        if ($this->frontendHelper->isFrontendRequest()) {
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
