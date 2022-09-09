<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Controller\Frontend;

use Oro\Bundle\FrontendLocalizationBundle\Helper\LocalizedSlugRedirectHelper;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Component\Routing\UrlUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ajax Localization Controller
 */
class AjaxLocalizationController extends AbstractController
{
    /**
     * @Route(
     *     "/set-current-localization",
     *     name="oro_frontend_localization_frontend_set_current_localization",
     *     methods={"POST"}
     * )
     * @CsrfProtection()
     */
    public function setCurrentLocalizationAction(Request $request): JsonResponse
    {
        $localization = $this->get(LocalizationManager::class)
            ->getLocalization($request->get('localization'), false);

        $localizationManager = $this->get(UserLocalizationManager::class);
        if ($localization instanceof Localization
            && array_key_exists($localization->getId(), $localizationManager->getEnabledLocalizations())
        ) {
            $localizationManager->setCurrentLocalization($localization);

            $redirectHelper = $this->get('oro_locale.helper.localized_slug_redirect');
            $fromUrl = $this->generateUrlWithContext($request);
            $toUrl = $redirectHelper->getLocalizedUrl($fromUrl, $localization);
            $toUrl = $this->rebuildQueryString($toUrl, $request);

            return new JsonResponse(['success' => true, 'redirectTo' => $toUrl]);
        }

        return new JsonResponse(['success' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LocalizationManager::class,
                UserLocalizationManager::class,
                'oro_locale.helper.localized_slug_redirect' => LocalizedSlugRedirectHelper::class
            ]
        );
    }

    private function generateUrlWithContext(Request $request)
    {
        $route = $request->get('redirectRoute', 'oro_frontend_root');
        $routeParams = json_decode($request->get('redirectRouteParameters'), true) ?? [];
        $contextParams = $routeParams['_context_url_attributes'] ?? [];

        if (isset($routeParams['_used_slug_url'])) {
            $usedSlugUrl = array_shift($routeParams['_used_slug_url']);
            if (!empty($routeParams['_used_slug_url'])) {
                $routeParams['_used_slug_url'][] = SluggableUrlGenerator::CONTEXT_DELIMITER;
            }
            $routeParams['_used_slug_url'][] = $usedSlugUrl;

            return UrlUtil::join(...$routeParams['_used_slug_url']);
        }

        $url = $this->generateUrl($route, $routeParams['_route_params'] ?? $routeParams);

        $urlParts = [];
        foreach ($contextParams as $param) {
            if (isset($param['_route'], $param['_route_params'])) {
                $contextUrl = $this->generateUrl($param['_route'], $param['_route_params']);

                if (isset($routeParams['_resolved_slug_url'], $param['_resolved_slug_url'])
                    && $routeParams['_resolved_slug_url'] !== $url
                    && $param['_resolved_slug_url'] !== $contextUrl
                ) {
                    $urlParts[] = $contextUrl;
                }
            }
        }

        if (!empty($urlParts)) {
            $urlParts[] = SluggableUrlGenerator::CONTEXT_DELIMITER;
            $urlParts[] = parse_url($url, PHP_URL_PATH);
            UrlUtil::join(...$urlParts);
        }

        return $url;
    }

    private function rebuildQueryString(string $toUrl, Request $request): string
    {
        $redirectQueryParams = json_decode($request->get('redirectQueryParameters'), true) ?? [];

        if (!empty($redirectQueryParams)) {
            parse_str(parse_url($toUrl, PHP_URL_QUERY), $existingQueryParams);
            if (!empty($existingQueryParams)) {
                $diffQueryParams = array_diff_assoc($redirectQueryParams, $existingQueryParams);
                $toUrl .= !empty($diffQueryParams) ? '&' . http_build_query($diffQueryParams) : '';
            } else {
                $toUrl .= '?' . http_build_query($redirectQueryParams);
            }
        }

        return $toUrl;
    }
}
