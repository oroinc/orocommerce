<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Page should be rendered in requested localization by slug if possible to detect.
 * Otherwise - by visitor preferences of default localization in system config
 */
class SlugDetectLocalizationExtension implements CurrentLocalizationExtensionInterface
{
    private RequestStack $requestStack;

    private UserLocalizationManagerInterface $localizationManager;

    private ManagerRegistry $registry;

    /** @var Localization|null|bool */
    private $slugLocalization = false;

    public function __construct(
        RequestStack $requestStack,
        UserLocalizationManagerInterface $localizationManager,
        ManagerRegistry $registry
    ) {
        $this->requestStack = $requestStack;
        $this->localizationManager = $localizationManager;
        $this->registry = $registry;
    }

    public function getCurrentLocalization(): ?Localization
    {
        if ($this->slugLocalization === false) {
            $request = $this->requestStack->getMainRequest();
            if ($request && $request->attributes->has('_used_slug')) {
                /** @var Slug $usedSlug */
                $usedSlug = $request->attributes->get('_used_slug');
                $this->registry->getManagerForClass(Slug::class)?->refresh($usedSlug);
                // save slug's localization after refresh to avoid the repeating of refresh query.
                $this->slugLocalization = $usedSlug->getLocalization();
                $this->assertLocalizationEnabled();
            }
        }

        return $this->slugLocalization ?: null;
    }

    private function assertLocalizationEnabled(): void
    {
        if ($this->slugLocalization) {
            $localizationId = $this->slugLocalization->getId();
            if (!array_key_exists($localizationId, $this->localizationManager->getEnabledLocalizations())) {
                $this->slugLocalization = null;
            }
        }
    }
}
