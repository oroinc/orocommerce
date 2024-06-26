<?php

namespace Oro\Bundle\UPSBundle\Model;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;

/**
 * UPS Price Request model
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PriceRequest
{
    protected ?string $username = null;
    protected ?string $password = null;
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;
    protected ?string $accessLicenseNumber = null;
    protected ?string $requestOption = null;
    protected ?string $serviceDescription = null;
    protected ?string $serviceCode = null;
    protected array $serviceCodes = [];
    protected ?string $shipperName = null;
    protected ?string $shipperNumber = null;
    protected ?AddressInterface $shipperAddress = null;
    protected ?string $shipFromName = null;
    protected ?AddressInterface $shipFromAddress = null;
    protected ?string $shipToName = null;
    protected ?AddressInterface $shipToAddress = null;

    /** @var Package[] */
    protected array $packages = [];

    /**
     * @return string
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function toJson(): string
    {
        if (empty($this->clientId)
            && empty($this->clientSecret)
        ) {
            $request['UPSSecurity'] = [
                'UsernameToken' => [
                    'Username' => $this->username,
                    'Password' => $this->password,
                ],
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => $this->accessLicenseNumber,
                ]
            ];
        }

        $request['RateRequest'] = [
            'Request' => [
                'RequestOption' => $this->requestOption,
            ],
            'Shipment' => [
                'Shipper' => [
                    'Name' => $this->shipperName,
                    'ShipperNumber' => $this->shipperNumber,
                    'Address' => $this->shipperAddress ? [
                        'AddressLine' => [
                            $this->shipperAddress->getStreet(),
                            $this->shipperAddress->getStreet2()
                        ],
                        'City' => $this->shipperAddress->getCity(),
                        'StateProvinceCode' => $this->shipperAddress->getRegionCode(),
                        'PostalCode' => $this->shipperAddress->getPostalCode(),
                        'CountryCode' => $this->shipperAddress->getCountryIso2(),
                    ] : [],
                ],
                'ShipTo' => [
                    'Name' => $this->shipToName,
                    'Address' => $this->shipToAddress ? [
                        'AddressLine' => [
                            $this->shipToAddress->getStreet(),
                            $this->shipToAddress->getStreet2()
                        ],
                        'City' => $this->shipToAddress->getCity(),
                        'StateProvinceCode' => $this->shipToAddress->getRegionCode(),
                        'PostalCode' => $this->shipToAddress->getPostalCode(),
                        'CountryCode' => $this->shipToAddress->getCountryIso2(),
                    ] : [],
                ],
                'ShipFrom' => [
                    'Name' => $this->shipFromName,
                    'Address' => $this->shipFromAddress ? [
                        'AddressLine' => [
                            $this->shipFromAddress->getStreet(),
                            $this->shipFromAddress->getStreet2()
                        ],
                        'City' => $this->shipFromAddress->getCity(),
                        'StateProvinceCode' => $this->shipFromAddress->getRegionCode(),
                        'PostalCode' => $this->shipFromAddress->getPostalCode(),
                        'CountryCode' => $this->shipFromAddress->getCountryIso2(),
                    ] : [],
                ],
                'Package' => array_map(function (Package $package) {
                    return $package->toArray();
                }, $this->packages),
            ]
        ];

        if ($this->getServiceCode() && $this->getServiceDescription()) {
            $request['RateRequest']['Shipment']['Service'] = [
                'Code' => $this->serviceCode,
                'Description' => $this->serviceDescription,
            ];
        } elseif (!empty($this->serviceCodes)) {
            /**
             * The following Services are not available to return shipment: 13, 59, 82, 83, 84, 85, 86
             * https://developer.ups.com/api/reference?loc=en_US#operation/Shipment
             */
            $restrictedServices = [13, 59, 82, 83, 84, 85, 86];
            foreach ($this->serviceCodes as $serviceCode) {
                if (isset($restrictedServices[(int) $serviceCode])) {
                    continue;
                }
                $request['RateRequest']['Shipment']['Service'][] = [
                    'Code' => (string) $serviceCode
                ];
            }
        }

        return json_encode($request);
    }

    /**
     * @param $username
     * @param $password
     * @param $accessLicenseNumber
     * @return $this
     */
    public function setSecurity($username, $password, $accessLicenseNumber)
    {
        $this->username = $username;
        $this->password = $password;
        $this->accessLicenseNumber = $accessLicenseNumber;

        return $this;
    }

    /**
     * @param string $name
     * @param string $shipperNumber
     * @param AddressInterface $address
     * @return $this
     */
    public function setShipper($name, $shipperNumber, AddressInterface $address)
    {
        $this->shipperName = $name;
        $this->shipperNumber = $shipperNumber;
        $this->shipperAddress = $address;

        return $this;
    }

    /**
     * @param string $name
     * @param AddressInterface $address
     * @return $this
     */
    public function setShipFrom($name, AddressInterface $address)
    {
        $this->shipFromName = $name;
        $this->shipFromAddress = $address;

        return $this;
    }

    /**
     * @param string $name
     * @param AddressInterface $address
     * @return $this
     */
    public function setShipTo($name, AddressInterface $address)
    {
        $this->shipToName = $name;
        $this->shipToAddress = $address;

        return $this;
    }

    /**
     * @param string $code
     * @param string $description
     * @return $this
     */
    public function setService($code, $description)
    {
        $this->serviceCode = $code;
        $this->serviceDescription = $description;

        return $this;
    }

    public function addPackage(Package $package)
    {
        $this->packages[] = $package;
    }

    public function removePackage(Package $package)
    {
        $this->packages = array_diff($this->packages, [$package]);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessLicenseNumber()
    {
        return $this->accessLicenseNumber;
    }

    /**
     * @param string $accessLicenseNumber
     * @return $this
     */
    public function setAccessLicenseNumber($accessLicenseNumber)
    {
        $this->accessLicenseNumber = $accessLicenseNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getRequestOption()
    {
        return $this->requestOption;
    }

    /**
     * @param string $requestOption
     * @return $this
     */
    public function setRequestOption($requestOption)
    {
        $this->requestOption = $requestOption;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceDescription()
    {
        return $this->serviceDescription;
    }

    /**
     * @param string $serviceDescription
     * @return $this
     */
    public function setServiceDescription($serviceDescription)
    {
        $this->serviceDescription = $serviceDescription;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceCode()
    {
        return $this->serviceCode;
    }

    /**
     * @param string $serviceCode
     * @return $this
     */
    public function setServiceCode($serviceCode)
    {
        $this->serviceCode = $serviceCode;

        return $this;
    }

    /**
     * @param array $serviceCode
     * @return $this
     */
    public function setServiceCodes(array $serviceCodes)
    {
        $this->serviceCodes = $serviceCodes;

        return $this;
    }

    /**
     * @return string
     */
    public function getShipperName()
    {
        return $this->shipperName;
    }

    /**
     * @param string $shipperName
     * @return $this
     */
    public function setShipperName($shipperName)
    {
        $this->shipperName = $shipperName;

        return $this;
    }

    /**
     * @return string
     */
    public function getShipperNumber()
    {
        return $this->shipperNumber;
    }

    /**
     * @param string $shipperNumber
     * @return $this
     */
    public function setShipperNumber($shipperNumber)
    {
        $this->shipperNumber = $shipperNumber;

        return $this;
    }

    /**
     * @return AddressInterface
     */
    public function getShipperAddress()
    {
        return $this->shipperAddress;
    }

    /**
     * @param AddressInterface $shipperAddress
     * @return $this
     */
    public function setShipperAddress(AddressInterface $shipperAddress)
    {
        $this->shipperAddress = $shipperAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getShipFromName()
    {
        return $this->shipFromName;
    }

    /**
     * @param string $shipFromName
     * @return $this
     */
    public function setShipFromName($shipFromName)
    {
        $this->shipFromName = $shipFromName;

        return $this;
    }

    /**
     * @return AddressInterface
     */
    public function getShipFromAddress()
    {
        return $this->shipFromAddress;
    }

    /**
     * @param AddressInterface $shipFromAddress
     * @return $this
     */
    public function setShipFromAddress(AddressInterface $shipFromAddress)
    {
        $this->shipFromAddress = $shipFromAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getShipToName()
    {
        return $this->shipToName;
    }

    /**
     * @param string $shipToName
     * @return $this
     */
    public function setShipToName($shipToName)
    {
        $this->shipToName = $shipToName;

        return $this;
    }

    /**
     * @return AddressInterface
     */
    public function getShipToAddress()
    {
        return $this->shipToAddress;
    }

    /**
     * @param AddressInterface $shipToAddress
     * @return $this
     */
    public function setShipToAddress(AddressInterface $shipToAddress)
    {
        $this->shipToAddress = $shipToAddress;

        return $this;
    }

    /**
     * @return Package[]
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param Package[] $packages
     * @return $this
     */
    public function setPackages(array $packages)
    {
        $this->packages = $packages;

        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): PriceRequest
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): PriceRequest
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }
}
