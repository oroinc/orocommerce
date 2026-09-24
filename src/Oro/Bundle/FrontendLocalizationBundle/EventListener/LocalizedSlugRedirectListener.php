<?php

namespace Oro\Bundle\FrontendLocalizationBundle\EventListener;

use Oro\Bundle\FrontendLocalizationBundle\Helper\LocalizedSlugRedirectHelper;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\SlugRedirectMatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Redirects the visitor to the slug of their own localization when the requested URL has moved in that
 * localization but is still a live slug of another one, so the slug matching does not raise a 404 and
 * the stored redirect is never reached.
 */
class LocalizedSlugRedirectListener
{
    public function __construct(
        private SlugRedirectMatcher $redirectMatcher,
        private LocalizedSlugRedirectHelper $localizedSlugRedirectHelper,
        private UserLocalizationManagerInterface $userLocalizationManager
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->hasResponse()) {
            return;
        }

        $request = $event->getRequest();
        $localization = $this->getVisitorLocalizationForForeignSlug($request);
        if (null === $localization) {
            return;
        }

        // The matched slug belongs to another localization: redirect only when this URL is a known
        // redirect source, otherwise it is a foreign URL the visitor opened on purpose.
        $pathInfo = $request->getPathInfo();
        $attributes = $this->redirectMatcher->match($pathInfo);
        if (!$attributes) {
            return;
        }

        $localizedUrl = $this->localizedSlugRedirectHelper->getLocalizedUrl($pathInfo, $localization);
        if ($localizedUrl === $pathInfo) {
            return;
        }

        $queryString = $request->getQueryString();
        if ($queryString) {
            $localizedUrl .= '?' . $queryString;
        }

        $event->setResponse(new RedirectResponse($localizedUrl, $attributes['statusCode']));
    }

    /**
     * Returns the visitor localization when the matched slug belongs to another one, null otherwise.
     */
    private function getVisitorLocalizationForForeignSlug(Request $request): ?Localization
    {
        $usedSlug = $request->attributes->get('_used_slug');
        if (!$usedSlug instanceof Slug) {
            return null;
        }

        // A single enabled localization leaves no room for a URL moved in one localization only.
        if (count($this->userLocalizationManager->getEnabledLocalizations()) <= 1) {
            return null;
        }

        $localization = $this->userLocalizationManager->getCurrentLocalization();
        if (null === $localization || $localization->getId() === $usedSlug->getLocalization()?->getId()) {
            return null;
        }

        return $localization;
    }
}
