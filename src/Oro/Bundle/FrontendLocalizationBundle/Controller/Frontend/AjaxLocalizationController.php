<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Controller\Frontend;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setCurrentLocalizationAction(Request $request)
    {
        $localization = $this->get(LocalizationManager::class)
            ->getLocalization($request->get('localization'), false);
        $userLocalizationManager = $this->get(UserLocalizationManager::class);

        $result = false;
        if (array_key_exists($localization->getId(), $userLocalizationManager->getEnabledLocalizations())) {
            $userLocalizationManager->setCurrentLocalization($localization);
            $result = true;
        }

        return new JsonResponse(['success' => $result]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LocalizationManager::class,
                UserLocalizationManager::class,
            ]
        );
    }
}
