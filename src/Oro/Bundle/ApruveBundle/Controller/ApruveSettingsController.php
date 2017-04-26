<?php

namespace Oro\Bundle\ApruveBundle\Controller;

use Oro\Bundle\ApruveBundle\Connection\Validator\Result\ApruveConnectionValidatorResultInterface;
use Oro\Bundle\ApruveBundle\Connection\Validator\Result\Factory\Merchant;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
        $tokenGenerator = $this->get('oro_security.generator.random_token');

        return new JsonResponse([
            'success' => true,
            'token' => $tokenGenerator->generateToken(),
        ]);
    }

    /**
     * @Route("/validate-connection/{channelId}/", name="oro_apruve_validate_connection")
     * @ParamConverter("channel", class="OroIntegrationBundle:Channel", options={"id" = "channelId"})
     * @Method("POST")
     *
     * @param Request      $request
     * @param Channel|null $channel
     *
     * @return JsonResponse
     */
    public function validateConnectionAction(Request $request, Channel $channel = null)
    {
        if (!$channel) {
            $channel = new Channel();
        }

        $form = $this->createForm(
            $this->get('oro_integration.form.type.channel'),
            $channel
        );
        $form->handleRequest($request);

        /** @var ApruveSettings $transport */
        $transport = $channel->getTransport();
        $result = $this->get('oro_apruve.connection.validator')->validateConnectionByApruveSettings($transport);

        if (!$result->getStatus()) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->getErrorMessageByValidatorResult($result),
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => $this->get('translator')->trans('oro.apruve.check_connection.result.success.message'),
        ]);
    }

    /**
     * @param ApruveConnectionValidatorResultInterface $result
     *
     * @return string
     */
    private function getErrorMessageByValidatorResult(ApruveConnectionValidatorResultInterface $result)
    {
        $message = 'oro.apruve.check_connection.result.server_error.message';
        $parameters = [
            '%error_message%' => trim($result->getErrorMessage(), '.')
        ];
        switch ($result->getErrorSeverity()) {
            case Merchant\GetMerchantRequestApruveConnectionValidatorResultFactory::INVALID_API_KEY_SEVERITY:
                $message = 'oro.apruve.check_connection.result.invalid_api_key.message';
                break;
            case Merchant\GetMerchantRequestApruveConnectionValidatorResultFactory::MERCHANT_NOT_FOUND_SEVERITY:
                $message = 'oro.apruve.check_connection.result.merchant_not_found.message';
                break;
        }
        return $this->get('translator')->trans($message, $parameters);
    }
}
