<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Stub;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;
use Oro\Bundle\SoapBundle\Client\SoapClientInterface;

class SoapClientStub implements SoapClientInterface
{
    public const AUTHORIZATION_ERROR_OPTION = 'authorization_error';
    public const CONNECTION_ERROR_OPTION = 'connection_error';
    public const NO_SERVICES_OPTION = 'no_services';
    public const OK_OPTION = 'ok';

    #[\Override]
    public function send(SoapClientSettingsInterface $settings, array $data)
    {
        switch ($data['WebAuthenticationDetail']['UserCredential']['Key']) {
            case self::AUTHORIZATION_ERROR_OPTION:
                return $this->createErrorResponse(FedexRateServiceResponse::AUTHORIZATION_ERROR);
            case self::CONNECTION_ERROR_OPTION:
                return $this->createErrorResponse(FedexRateServiceResponse::CONNECTION_ERROR);
            case self::NO_SERVICES_OPTION:
                return $this->createErrorResponse(FedexRateServiceResponse::NO_SERVICES_ERROR);
            case self::OK_OPTION:
                return $this->createOkResponse();
        }

        return $this->createErrorResponse(FedexRateServiceResponse::CONNECTION_ERROR);
    }

    private function createErrorResponse(int $code): \StdClass
    {
        return (object) [
            'HighestSeverity' => FedexRateServiceResponse::SEVERITY_ERROR,
            'Notifications' => (object) [
                'Code' => $code,
            ]
        ];
    }

    private function createOkResponse(): \StdClass
    {
        return (object) [
            'HighestSeverity' => FedexRateServiceResponse::SEVERITY_SUCCESS,
            'Notifications' => (object) [
                'Code' => 0,
            ],
            'RateReplyDetails' => (object) [
                'ServiceType' => 'service',
                'RatedShipmentDetails' => (object) [
                    'ShipmentRateDetail' => (object) [
                        'TotalNetCharge' => (object) [
                            'Amount' => '25',
                            'Currency' => 'USD',
                        ]
                    ]
                ]
            ]
        ];
    }
}
