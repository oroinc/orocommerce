<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Request\Builder;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequest;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder\TimeInTransitRequestBuilder;

class TimeInTransitRequestBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const UPS_API_USERNAME = 'user';
    private const UPS_API_PASSWORD = 'pass';
    private const UPS_API_KEY = 'key';
    private const UPS_OAUTH_CLIENT_ID = null;
    private const UPS_OAUTH_CLIENT_SECRET = null;
    private const WEIGHT_UNIT_CODE = 'LBS';
    private const WEIGHT = '1';
    private const CUSTOMER_CONTEXT = 'sample context';
    private const TRANSACTION_IDENTIFIER = 'sample id';
    private const MAXIMUM_LIST_SIZE = '1';

    /** @var \DateTime */
    private $pickupDate;

    /** @var AddressInterface */
    private $address;

    protected function setUp(): void
    {
        $this->pickupDate = new \DateTime();
        $this->address = new AddressStub();
    }

    public function testCreate()
    {
        $expected = new UpsClientRequest([
            UpsClientRequest::FIELD_URL => 'TimeInTransit',
            UpsClientRequest::FIELD_REQUEST_DATA => $this->getRequestData(),
        ]);

        $builder = new TimeInTransitRequestBuilder(
            self::UPS_API_USERNAME,
            self::UPS_API_PASSWORD,
            self::UPS_API_KEY,
            $this->address,
            $this->address,
            $this->pickupDate
        );

        $builder->setUpsClientId(self::UPS_OAUTH_CLIENT_ID);
        $builder->setUpsClientSecret(self::UPS_OAUTH_CLIENT_SECRET);

        self::assertEquals($expected, $builder->createRequest());
    }

    public function testCreateWithOptionalParams()
    {
        $expected = new UpsClientRequest([
            UpsClientRequest::FIELD_URL => 'TimeInTransit',
            UpsClientRequest::FIELD_REQUEST_DATA => $this->getRequestDataWithOptionalParams(),
        ]);

        $builder = new TimeInTransitRequestBuilder(
            self::UPS_API_USERNAME,
            self::UPS_API_PASSWORD,
            self::UPS_API_KEY,
            $this->address,
            $this->address,
            $this->pickupDate
        );

        $builder->setUpsClientId(self::UPS_OAUTH_CLIENT_ID);
        $builder->setUpsClientSecret(self::UPS_OAUTH_CLIENT_SECRET);

        $builder
            ->setMaximumListSize(self::MAXIMUM_LIST_SIZE)
            ->setWeight(self::WEIGHT, self::WEIGHT_UNIT_CODE)
            ->setCustomerContext(self::CUSTOMER_CONTEXT)
            ->setTransactionIdentifier(self::TRANSACTION_IDENTIFIER);

        self::assertEquals($expected, $builder->createRequest());
    }

    private function getRequestData(): array
    {
        $addressArray = [
            'StateProvinceCode' => $this->address->getRegionCode(),
            'PostalCode' => $this->address->getPostalCode(),
            'CountryCode' => $this->address->getCountryIso2(),
            'City' => $this->address->getCity(),
        ];

        return [
            'Security' => [
                'UsernameToken' => [
                    'Username' => self::UPS_API_USERNAME,
                    'Password' => self::UPS_API_PASSWORD,
                ],
                'UPSServiceAccessToken' => [
                    'AccessLicenseNumber' => self::UPS_API_KEY,
                ],
            ],
            'TimeInTransitRequest' => [
                'Request' => [
                    'RequestOption' => 'TNT',
                ],
                'ShipFrom' => [
                    'Address' => $addressArray,
                ],
                'ShipTo' => [
                    'Address' => $addressArray,
                ],
                'Pickup' => [
                    'Date' => $this->pickupDate->format('Ymd'),
                ],
            ],
        ];
    }

    private function getRequestDataWithOptionalParams(): array
    {
        return array_merge_recursive($this->getRequestData(), [
            'TimeInTransitRequest' => [
                'Request' => [
                    'TransactionReference' => [
                        'CustomerContext' => self::CUSTOMER_CONTEXT,
                        'TransactionIdentifier' => self::TRANSACTION_IDENTIFIER,
                    ],
                ],
                'ShipmentWeight' => [
                    'UnitOfMeasurement' => [
                        'Code' => self::WEIGHT_UNIT_CODE,
                    ],
                    'Weight' => self::WEIGHT,
                ],
                'MaximumListSize' => self::MAXIMUM_LIST_SIZE,
            ],
        ]);
    }
}
