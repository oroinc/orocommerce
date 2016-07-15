<?php

namespace OroB2B\Bundle\WebsiteBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxLocalizationController extends Controller
{
    /**
     * @Route("/set-current-localization", name="orob2b_website_frontend_set_current_localization")
     * @Method({"POST"})
     *
     * {@inheritdoc}
     */
    public function setCurrentCurrencyAction(Request $request)
    {
        $result = false;
        $localizationId = $request->get('localization');
        $userLocalizationManager = $this->get('orob2b_website.user_localization_manager');
        $localization = $this->get('oro_locale.provider.localization')->getLocalization($localizationId);
        if (in_array($localization, $userLocalizationManager->getEnabledLocalizations(), true)) {
            $userLocalizationManager->setCurrentLocalization($localizationId);
            $result = true;
        }

        return new JsonResponse(['success' => $result]);
    }
}
