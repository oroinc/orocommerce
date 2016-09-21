<?php

namespace Oro\Bundle\UPSBundle\Model;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;

class PriceRequest
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $accessLicenseNumber;

    /**
     * @var string
     */
    protected $requestOption;

    /**
     * @var string
     */
    protected $serviceDescription;

    /**
     * @var string
     */
    protected $serviceCode;

    /**
     * @var string
     */
    protected $shipperName;

    /**
     * @var string
     */
    protected $shipperNumber;

    /**
     * @var AddressInterface
     */
    protected $shipperAddress;

    /**
     * @var string
     */
    protected $shipFromName;

    /**
     * @var AddressInterface
     */
    protected $shipFromAddress;

    /**
     * @var string
     */
    protected $shipToName;

    /**
     * @var AddressInterface
     */
    protected $shipToAddress;


    /**
     * @var Package[]
     */
    protected $packages = [];

    /**
     * @return string
     */
    public function toJson()
    {
        $request = [
            'UPSSecurity' => [
                    'UsernameToken'      => [
                            'Username' => $this->username,
                            'Password' => $this->password,
                        ],
                    'ServiceAccessToken' => [
                            'AccessLicenseNumber' => $this->accessLicenseNumber,
                        ],
                ],
            'RateRequest' => [
                    'Request'  => [
                            'RequestOption' => $this->requestOption,
                        ],
                    'Shipment' => [
                            'Shipper'  => [
                                    'Name'          => $this->shipperName,
                                    'ShipperNumber' => $this->shipperNumber,
                                    'Address'       => [
                                            'AddressLine'       => [
                                                    $this->shipperAddress->getStreet(),
                                                    $this->shipperAddress->getStreet2()
                                                ],
                                            'City'              => $this->shipperAddress->getCity(),
                                            'StateProvinceCode' => $this->shipperAddress->getRegionCode(),
                                            'PostalCode'        => $this->shipperAddress->getPostalCode(),
                                            'CountryCode'       => $this->shipperAddress->getCountryIso2(),
                                        ],
                                ],
                            'ShipTo'   => [
                                    'Name'    => $this->shipToName,
                                    'Address' => [
                                            'AddressLine'       => [
                                                    $this->shipToAddress->getStreet(),
                                                    $this->shipToAddress->getStreet2()
                                                ],
                                            'City'              => $this->shipToAddress->getCity(),
                                            'StateProvinceCode' => $this->shipToAddress->getRegionCode(),
                                            'PostalCode'        => $this->shipToAddress->getPostalCode(),
                                            'CountryCode'       => $this->shipToAddress->getCountryIso2(),
                                        ],
                                ],
                            'ShipFrom' => [
                                    'Name'    => $this->shipFromName,
                                    'Address' => [
                                            'AddressLine'       => [
                                                    $this->shipFromAddress->getStreet(),
                                                    $this->shipFromAddress->getStreet2()
                                                ],
                                            'City'              => $this->shipFromAddress->getCity(),
                                            'StateProvinceCode' => $this->shipFromAddress->getRegionCode(),
                                            'PostalCode'        => $this->shipFromAddress->getPostalCode(),
                                            'CountryCode'       => $this->shipFromAddress->getCountryIso2(),
                                        ],
                                ],
                            'Package'  => array_map(
                                function ($package) {
                                    /** @var $package Package */
                                    return $package->toArray();
                                },
                                $this->packages
                            ),
                        ],
                ],
        ];

        if (null !== $this->getServiceCode() && null !== $this->getServiceDescription()) {
            $request['RateRequest']['Shipment']['Service'] = [
                'Code'        => $this->serviceCode,
                'Description' => $this->serviceDescription,
            ];
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

    /**
     * @param Package $package
     */
    public function addPackage(Package $package)
    {
        $this->packages[] = $package;
    }

    /**
     * @param Package $package
     */
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
}
