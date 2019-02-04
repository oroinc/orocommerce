<?php

namespace Oro\Bundle\ConsentBundle\Builder;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * Use it to build ConsentData and CmsPageData DTOs from Consent object
 */
class ConsentDataBuilder
{
    /**
     * @var ConsentAcceptanceProvider
     */
    protected $consentAcceptanceProvider;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var CmsPageDataBuilder
     */
    protected $cmsPageDataBuilder;

    /**
     * @param ConsentAcceptanceProvider $consentAcceptanceProvider
     * @param LocalizationHelper  $localizationHelper
     * @param CmsPageDataBuilder  $cmsPageDataBuilder
     */
    public function __construct(
        ConsentAcceptanceProvider $consentAcceptanceProvider,
        LocalizationHelper $localizationHelper,
        CmsPageDataBuilder $cmsPageDataBuilder
    ) {
        $this->consentAcceptanceProvider = $consentAcceptanceProvider;
        $this->localizationHelper = $localizationHelper;
        $this->cmsPageDataBuilder = $cmsPageDataBuilder;
    }

    /**
     * @param Consent $consent
     *
     * @return ConsentData
     */
    public function build(Consent $consent)
    {
        $consentAcceptance = $this->consentAcceptanceProvider->getCustomerConsentAcceptanceByConsentId(
            $consent->getId()
        );
        $consentData = $this->getBuiltConsentData($consent, $consentAcceptance);
        $cmsPageData = $this->cmsPageDataBuilder->build($consent, $consentAcceptance);
        if ($cmsPageData instanceof CmsPageData) {
            $consentData->setCmsPageData($cmsPageData);
        }

        return $consentData;
    }

    /**
     * @param Consent                $consent
     * @param ConsentAcceptance|null $consentAcceptance
     *
     * @return ConsentData
     */
    protected function getBuiltConsentData(Consent $consent, ConsentAcceptance $consentAcceptance = null)
    {
        $consentData = new ConsentData($consent);

        $isConsentAccepted = null !== $consentAcceptance;
        $consentData->setAccepted($isConsentAccepted);

        $localizedTitle = (string) $this->localizationHelper->getLocalizedValue($consent->getNames());
        $consentData->setTitle($localizedTitle);

        return $consentData;
    }
}
