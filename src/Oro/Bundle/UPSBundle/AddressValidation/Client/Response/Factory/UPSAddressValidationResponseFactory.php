<?php

namespace Oro\Bundle\UPSBundle\AddressValidation\Client\Response\Factory;

use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponse;
use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponseInterface;
use Oro\Bundle\AddressValidationBundle\Client\Response\Factory\AddressValidationResponseFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

/**
 * UPS Address Validation rest API response factory.
 */
class UPSAddressValidationResponseFactory implements AddressValidationResponseFactoryInterface
{
    public function createExceptionResult(\Exception $exception): AddressValidationResponseInterface
    {
        $responseStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
        $errors = [];

        if ($exception instanceof RestException) {
            $responseStatus = $exception->getCode();
            try {
                $errors = $exception->getResponse()?->json()['response']['errors'] ?? [];
            } catch (\Exception) {
            }
        } else {
            $errors[] = $exception->getMessage();
        }

        return new AddressValidationResponse($responseStatus, [], $errors);
    }

    public function create(RestResponseInterface $response): AddressValidationResponseInterface
    {
        /** @var array $data */
        $data = $response->json();
        if (!\is_array($data)) {
            return new AddressValidationResponse(Response::HTTP_BAD_REQUEST);
        }

        $resolvedAddresses = $data['XAVResponse']['Candidate'] ?? [];

        return new AddressValidationResponse(Response::HTTP_OK, $resolvedAddresses);
    }
}
