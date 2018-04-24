<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FrontendBundle\Form\Type\CountryType;
use Oro\Bundle\FrontendBundle\Form\Type\RegionType;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\AbstractOrderAddressTypeTest;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class CheckoutAddressTypeTest extends AbstractOrderAddressTypeTest
{
    protected function initFormType()
    {
        $this->formType = new CheckoutAddressType(
            $this->addressFormatter,
            $this->orderAddressManager,
            $this->orderAddressSecurityProvider,
            $this->serializer
        );
        $this->formType->setDataClass('Oro\Bundle\OrderBundle\Entity\OrderAddress');
    }

    public function testGetParent()
    {
        $this->assertEquals(AddressType::class, $this->formType->getParent());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $ext = parent::getExtensions();
        return array_merge($ext, [new PreloadedExtension(
            [
                $this->formType,
                CountryType::class => new CountryType(),
                RegionType::class => new RegionType(),
            ],
            [FormType::class => [new AdditionalAttrExtension()]]
        )]);
    }

    /**
     * @return Checkout
     */
    protected function getEntity()
    {
        return new Checkout();
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     * @param CustomerAddress $savedAddress
     * @param string $addressType
     * @dataProvider submitWithPermissionAndCustomFieldsAndCustomerAddressProvider
     */
    public function testSubmitWithManualPermissionAndCustomFieldsAndAddressCustomer(
        $submittedData,
        $expectedData,
        $savedAddress,
        $addressType
    ) {
        $customerAddressIdentifier = $submittedData['customerAddress'];
        $this->serializer->expects($this->once())->method('normalize')->willReturn(['a_1' => ['street' => 'street']]);

        $this->addressCollection->expects($this->once())
            ->method('toArray')
            ->willReturn(['group_name' => [$customerAddressIdentifier => $savedAddress]]);
        $this->addressCollection->expects($this->once())
            ->method('getDefaultAddressKey')
            ->willReturn($customerAddressIdentifier);

        $this->orderAddressManager->expects($this->once())->method('getEntityByIdentifier')
            ->willReturn($savedAddress);

        $this->orderAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(true);

        $this->orderAddressManager->expects($this->once())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (CustomerAddress $address = null, OrderAddress $orderAddress = null) {
                        $orderAddress
                            ->setCustomerAddress($address)
                            ->setLabel($address->getLabel())
                            ->setCountry($address->getCountry())
                            ->setOrganization(static::ORGANIZATION)
                            ->setRegion($address->getRegion())
                            ->setCity($address->getCity())
                            ->setPostalCode($address->getPostalCode())
                            ->setStreet($address->getStreet());

                        return $orderAddress;
                    }
                )
            );

        $formOptions =  [
            'addressType' => $addressType,
            'object' => $this->getEntity(),
            'isEditEnabled' => true,
        ];

        $this->checkForm(true, $submittedData, $expectedData, new OrderAddress(), [], $formOptions);
    }

    public function testSubmitWithManualPermissionWhenNoDataSubmitted()
    {
        $this->addressCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $formOptions =  [
            'addressType' => AddressTypeEntity::TYPE_BILLING,
            'object' => $this->getEntity(),
            'isEditEnabled' => true,
        ];

        $this->checkForm(true, null, null, new OrderAddress(), [], $formOptions);
    }

    /**
     * @return array
     */
    public function submitWithPermissionAndCustomFieldsAndCustomerAddressProvider()
    {
        list($country, $region) = $this->getValidCountryAndRegion();

        $savedCustomerAddress = (new CustomerAddress())
            ->setLabel('Label')
            ->setCountry($country)
            ->setOrganization(static::ORGANIZATION)
            ->setRegion($region)
            ->setCity('City')
            ->setPostalCode('AL')
            ->setStreet('Street');

        $submittedData = [
            'customerAddress' => 'a_1',
            'label' => 'Label',
            'namePrefix' => 'NamePrefix',
            'firstName' => 'FirstName',
            'middleName' => 'MiddleName',
            'lastName' => 'LastName',
            'nameSuffix' => 'NameSuffix',
            'street' => 'Street',
            'street2' => 'Street2',
            'city' => 'City',
            'region' => self::REGION_WITH_COUNTRY,
            'region_text' => 'Region Text',
            'postalCode' => 'AL',
            'country' => self::COUNTRY_WITH_REGION,
        ];

        $expectedData = (new OrderAddress())
            ->setCustomerAddress($savedCustomerAddress)
            ->setLabel('Label')
            ->setStreet('Street')
            ->setCity('City')
            ->setRegion($region)
            ->setPostalCode('AL')
            ->setCountry($country)
            ->setOrganization(static::ORGANIZATION);

        return [
            'custom_address_info_submitted_together_with_chosen_customer_address_for_billing_address' => [
                'submittedData' => $submittedData,
                'expectedData' => $expectedData,
                'savedAddress' => $savedCustomerAddress,
                'addressType' => AddressTypeEntity::TYPE_BILLING
            ],
            'custom_address_info_submitted_together_with_chosen_customer_address_for_shipping_address' => [
                'submittedData' => $submittedData,
                'expectedData' => $expectedData,
                'savedAddress' => $savedCustomerAddress,
                'addressType' => AddressTypeEntity::TYPE_SHIPPING
            ]
        ];
    }
}
