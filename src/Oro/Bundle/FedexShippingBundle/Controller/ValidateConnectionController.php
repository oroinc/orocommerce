<?php

namespace Oro\Bundle\FedexShippingBundle\Controller;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * FedEx Validate Connection Controller
 */
class ValidateConnectionController extends AbstractController
{
    /**
     * @Route("/validate-connection/{channelId}/", name="oro_fedex_validate_connection", methods={"POST"})
     * @ParamConverter("channel", class="OroIntegrationBundle:Channel", options={"id" = "channelId"})
     * @CsrfProtection()
     *
     * @throws \InvalidArgumentException
     */
    public function validateConnectionAction(Request $request, Channel $channel = null): JsonResponse
    {
        if (!$this->isShippingOriginProvided()) {
            return new JsonResponse([
                'success' => false,
                'message' => $this
                    ->get('translator')
                    ->trans('oro.fedex.connection_validation.result.no_shipping_origin_error.message'),
            ]);
        }

        if (!$channel) {
            $channel = new Channel();
        }

        $form = $this->createForm(
            ChannelType::class,
            $channel
        );
        $form->handleRequest($request);

        /** @var FedexIntegrationSettings $settings */
        $settings = $channel->getTransport();

        $request = $this
            ->get('oro_fedex_shipping.client.rate_service.connection_validate_request.factory')
            ->create($settings);
        $response = $this->get('oro_fedex_shipping.client.rate_service')->send($request, $settings);

        if (!empty($response->getPrices())) {
            return new JsonResponse([
                'success' => true,
                'message' => $this->get('translator')->trans('oro.fedex.connection_validation.result.success.message'),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => $this->get('translator')->trans($this->getErrorMessageTranslation($response)),
        ]);
    }

    private function getErrorMessageTranslation(FedexRateServiceResponseInterface $response): string
    {
        if ($response->getSeverityCode() === FedexRateServiceResponse::AUTHORIZATION_ERROR) {
            return 'oro.fedex.connection_validation.result.authorization_error.message';
        } elseif ($response->getSeverityCode() === FedexRateServiceResponse::CONNECTION_ERROR) {
            return 'oro.fedex.connection_validation.result.connection_error.message';
        } elseif (empty($response->getPrices())) {
            return 'oro.fedex.connection_validation.result.no_services_error.message';
        }

        return 'oro.fedex.connection_validation.result.connection_error.message';
    }

    private function isShippingOriginProvided(): bool
    {
        $shippingOrigin = $this->get('oro_shipping.shipping_origin.provider')->getSystemShippingOrigin();

        return $shippingOrigin->getCountry() !== null;
    }
}
