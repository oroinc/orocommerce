<?php

namespace Oro\Bundle\FedexShippingBundle\AddressValidation\Client\Response\Factory;

use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponse;
use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponseInterface;
use Oro\Bundle\AddressValidationBundle\Client\Response\Factory\AddressValidationResponseFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates FedEx Address Validation response by a given rest response.
 */
class FedexAddressValidationResponseFactory implements AddressValidationResponseFactoryInterface
{
    public function create(RestResponseInterface $response): AddressValidationResponseInterface
    {
        /** @var array $data */
        $data = $response->json();
        if (!\is_array($data)) {
            return new AddressValidationResponse(Response::HTTP_BAD_REQUEST);
        }

        $resolvedAddresses = $data['output']['resolvedAddresses'] ?? [];

        return new AddressValidationResponse(Response::HTTP_OK, $resolvedAddresses);
    }

    public function createExceptionResult(\Exception $exception): AddressValidationResponseInterface
    {
        $responseStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
        $errors = [];

        if ($exception instanceof RestException) {
            $responseStatus = $exception->getCode();
            try {
                $errors = $exception->getResponse()?->json()['errors'] ?? [];
            } catch (\Exception) {
            }
        } else {
            $errors[] = $exception->getMessage();
        }

        return new AddressValidationResponse($responseStatus, [], $errors);
    }
}
