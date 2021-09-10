<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Extension;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tries to get a current localization from "X-Localization-ID" header of the master request.
 */
class RequestCurrentLocalizationExtension implements CurrentLocalizationExtensionInterface
{
    private const LOCALIZATION_ID_HEADER = 'X-Localization-ID';

    /** @var RequestStack */
    private $requestStack;

    /** @var LocalizationManager */
    private $localizationManager;

    public function __construct(RequestStack $requestStack, LocalizationManager $localizationManager)
    {
        $this->requestStack = $requestStack;
        $this->localizationManager = $localizationManager;
    }

    /**
     * @return Localization|null
     */
    public function getCurrentLocalization()
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        $localizationId = $request->headers->get(self::LOCALIZATION_ID_HEADER);
        if (!$localizationId || false === filter_var($localizationId, FILTER_VALIDATE_INT)) {
            return null;
        }

        return $this->localizationManager->getLocalization((int)$localizationId);
    }
}
