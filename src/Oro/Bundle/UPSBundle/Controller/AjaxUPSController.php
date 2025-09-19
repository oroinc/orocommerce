<?php

namespace Oro\Bundle\UPSBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactory;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\UpsConnectionValidator;
use Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Ajax UPS Controller
 */
class AjaxUPSController extends AbstractController
{
    /**
     * @param Country $country
     * @return JsonResponse
     */
    #[Route(
        path: '/get-shipping-services-by-country/{code}',
        name: 'oro_ups_country_shipping_services',
        requirements: ['code' => '^[A-Z]{2}$'],
        methods: ['GET']
    )]
    public function getShippingServicesByCountryAction(
        #[MapEntity(id: 'code')]
        Country $country
    ) {
        /** @var ShippingServiceRepository $repository */
        $repository = $this->container->get('doctrine')
            ->getManagerForClass(ShippingService::class)
            ->getRepository(ShippingService::class);
        $services = $repository->getShippingServicesByCountry($country);
        $result = [];
        foreach ($services as $service) {
            $result[] = ['id' => $service->getId(), 'description' => $service->getDescription()];
        }
        return new JsonResponse($result);
    }

    /**
     *
     * @param Request      $request
     * @param Channel|null $channel
     *
     * @return JsonResponse
     */
    #[Route(path: '/validate-connection/{channelId}/', name: 'oro_ups_validate_connection', methods: ['POST'])]
    #[CsrfProtection()]
    public function validateConnectionAction(
        Request $request,
        #[MapEntity(id: 'channelId')]
        ?Channel $channel = null
    ) {
        if (!$channel) {
            $channel = new Channel();
        }

        $form = $this->createForm(
            ChannelType::class,
            $channel
        );
        $form->handleRequest($request);

        /** @var UPSTransport $transport */
        $transport = $channel->getTransport();
        $result = $this->container->get(UpsConnectionValidator::class)->validateConnectionByUpsSettings($transport);

        if (!$result->getStatus()) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->getErrorMessageByValidatorResult($result),
            ]);
        }

        $translator = $this->container->get(TranslatorInterface::class);

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('oro.ups.connection_validation.result.success.message'),
        ]);
    }

    /**
     * @param UpsConnectionValidatorResultInterface $result
     *
     * @return string
     */
    private function getErrorMessageByValidatorResult(UpsConnectionValidatorResultInterface $result)
    {
        $message = 'oro.ups.connection_validation.result.unexpected_error.message';
        $parameters = [
            '%error_message%' => trim($result->getErrorMessage(), '.')
        ];
        switch ($result->getErrorSeverity()) {
            case UpsConnectionValidatorResultFactory::AUTHENTICATION_SEVERITY:
                $message = 'oro.ups.connection_validation.result.authentication.message';
                break;
            case UpsConnectionValidatorResultFactory::MEASUREMENT_SYSTEM_SEVERITY:
                $message = 'oro.ups.connection_validation.result.measurement_system.message';
                break;
            case UpsConnectionValidatorResultFactory::SERVER_SEVERITY:
                $message = 'oro.ups.connection_validation.result.server_error.message';
                break;
        }
        return $this->container->get(TranslatorInterface::class)->trans($message, $parameters);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpsConnectionValidator::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
