<?php

namespace Oro\Bundle\ApruveBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApruveSettingsController extends Controller
{
    /**
     * @Route("/generate-token", name="oro_apruve_generate_token", options={"expose"=true})
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function generateTokenAction(Request $request)
    {
        $tokenGenerator = $this->get('oro_apruve.token_generator');

        return new JsonResponse([
            'success' => true,
            'token' => $tokenGenerator->generateToken(),
        ]);
    }
}
