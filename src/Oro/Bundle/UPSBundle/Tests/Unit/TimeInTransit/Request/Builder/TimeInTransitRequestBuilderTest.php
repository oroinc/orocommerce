<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Request\Builder;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequest;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder\TimeInTransitRequestBuilder;

class TimeInTransitRequestBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const UPS_API_USERNAME = 'user';

    /**
     * @internal
     */
    const UPS_API_PASSWORD = 'pass';

    /**
     * @internal
     */
    const UPS_API_KEY = 'key';

    /**
     * @internal
     */
    const WEIGHT_UNIT_CODE = 'LBS';

    /**
     * @internal
     */
    const WEIGHT = '1';

    /**
     * @internal
     */
    const CUSTOMER_CONTEXT = 'sample context';

    /**
     * @internal
     */
    const TRANSACTION_IDENTIFIER = 'sample id';

    /**
     * @internal
     */
    const MAXIMUM_LIST_SIZE = '1';

    /**
     * @var \DateTime
     */
    private $pickupDate;

    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->address = new AddressStub();
        $this->pickupDate = new \DateTime();
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

        static::assertEquals($expected, $builder->createRequest());
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

        $builder
            ->setMaximumListSize(self::MAXIMUM_LIST_SIZE)
            ->setWeight(self::WEIGHT, self::WEIGHT_UNIT_CODE)
            ->setCustomerContext(self::CUSTOMER_CONTEXT)
            ->setTransactionIdentifier(self::TRANSACTION_IDENTIFIER);

        static::assertEquals($expected, $builder->createRequest());
    }

    /**
     * @return array
     */
    private function getRequestData()
    {
        $addressArray = [
            'StateProvinceCode' => $this->address->getRegionCode(),
            'PostalCode' => $this->address->getPostalCode(),
            'CountryCode' => $this->address->getCountryIso2(),
            'City' => $this->address->getCity(),
        ];

        $request = [
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

        return $request;
    }

    /**
     * @return array
     */
    private function getRequestDataWithOptionalParams()
    {
        $request = $this->getRequestData();

        $request = array_merge_recursive($request, [
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

        return $request;
    }
}
