<?php

namespace Oro\Bundle\FedexShippingBundle\Controller;

use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceSoapClient;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRateServiceValidateConnectionRequestFactory;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\ShippingBundle\Provider\SystemShippingOriginProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $translator = $this->get(TranslatorInterface::class);
        if (!$this->isShippingOriginProvided()) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator
                    ->trans('oro.fedex.connection_validation.result.no_shipping_origin_error.message'),
            ]);
        }

        if (!$channel) {
            $channel = new Channel();
        }

        $form = $this->createForm(ChannelType::class, $channel);
        $form->handleRequest($request);

        /** @var FedexIntegrationSettings $settings */
        $settings = $channel->getTransport();

        $response = $this->get(FedexRateServiceSoapClient::class)->send(
            $this->get(FedexRateServiceValidateConnectionRequestFactory::class)->create($settings),
            $settings
        );

        if (!empty($response->getPrices())) {
            return new JsonResponse([
                'success' => true,
                'message' => $translator->trans('oro.fedex.connection_validation.result.success.message'),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => $translator->trans($this->getErrorMessageTranslation($response)),
        ]);
    }

    private function getErrorMessageTranslation(FedexRateServiceResponseInterface $response): string
    {
        if ($response->getSeverityCode() === FedexRateServiceResponse::AUTHORIZATION_ERROR) {
            return 'oro.fedex.connection_validation.result.authorization_error.message';
        }
        if ($response->getSeverityCode() === FedexRateServiceResponse::CONNECTION_ERROR) {
            return 'oro.fedex.connection_validation.result.connection_error.message';
        }
        if (empty($response->getPrices())) {
            return 'oro.fedex.connection_validation.result.no_services_error.message';
        }

        return 'oro.fedex.connection_validation.result.connection_error.message';
    }

    private function isShippingOriginProvided(): bool
    {
        $shippingOrigin = $this->get(SystemShippingOriginProvider::class)->getSystemShippingOrigin();

        return $shippingOrigin->getCountry() !== null;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                FedexRateServiceSoapClient::class,
                SystemShippingOriginProvider::class,
                FedexRateServiceValidateConnectionRequestFactory::class,
            ]
        );
    }
}
