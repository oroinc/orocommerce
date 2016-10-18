<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FrontendBundle\Form\Type\CountryType;
use Oro\Bundle\FrontendBundle\Form\Type\RegionType;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\AbstractOrderAddressTypeTest;

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

    public function testGetName()
    {
        $this->assertEquals(CheckoutAddressType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_address', $this->formType->getParent());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $ext = parent::getExtensions();
        return array_merge($ext, [new PreloadedExtension(
            [
            'oro_frontend_country' => new CountryType(),
            'oro_frontend_region' => new RegionType(),
            ],
            ['form' => [new AdditionalAttrExtension()]]
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
     * @param AccountAddress $savedAddress
     * @param string $addressType
     * @dataProvider submitWithPermissionAndCustomFieldsAndAccountAddressProvider
     */
    public function testSubmitWithManualPermissionAndCustomFieldsAndAddressAccount(
        $submittedData,
        $expectedData,
        $savedAddress,
        $addressType
    ) {
        $accountAddressIdentifier = $submittedData['accountAddress'];
        $this->serializer->expects($this->once())->method('normalize')->willReturn(['a_1' => ['street' => 'street']]);
        $this->orderAddressManager->expects($this->once())->method('getGroupedAddresses')
            ->willReturn(['group_name' => [$accountAddressIdentifier => $savedAddress]]);

        $this->orderAddressManager->expects($this->once())->method('getEntityByIdentifier')
            ->willReturn($savedAddress);

        $this->orderAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(true);

        $this->orderAddressManager->expects($this->once())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (AccountAddress $address = null, OrderAddress $orderAddress = null) {
                        $orderAddress
                            ->setAccountAddress($address)
                            ->setLabel($address->getLabel())
                            ->setCountry($address->getCountry())
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
        $this->orderAddressManager->expects($this->once())->method('getGroupedAddresses')
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
    public function submitWithPermissionAndCustomFieldsAndAccountAddressProvider()
    {
        $country = new Country('US');
        $region = (new Region('US-AL'))->setCountry($country);

        $savedAccountAddress = (new AccountAddress())
            ->setLabel('Label')
            ->setCountry($country)
            ->setRegion($region)
            ->setCity('City')
            ->setPostalCode('AL')
            ->setStreet('Street');

        $submittedData = [
            'accountAddress' => 'a_1',
            'label' => 'Label',
            'namePrefix' => 'NamePrefix',
            'firstName' => 'FirstName',
            'middleName' => 'MiddleName',
            'lastName' => 'LastName',
            'nameSuffix' => 'NameSuffix',
            'organization' => 'Organization',
            'street' => 'Street',
            'street2' => 'Street2',
            'city' => 'City',
            'region' => 'US-AL',
            'region_text' => 'Region Text',
            'postalCode' => 'AL',
            'country' => 'US',
        ];

        $expectedData = (new OrderAddress())
            ->setAccountAddress($savedAccountAddress)
            ->setLabel('Label')
            ->setStreet('Street')
            ->setCity('City')
            ->setRegion($region)
            ->setPostalCode('AL')
            ->setCountry($country);

        return [
            'custom_address_info_submitted_together_with_chosen_account_address_for_billing_address' => [
                'submittedData' => $submittedData,
                'expectedData' => $expectedData,
                'savedAddress' => $savedAccountAddress,
                'addressType' => AddressTypeEntity::TYPE_BILLING
            ],
            'custom_address_info_submitted_together_with_chosen_account_address_for_shipping_address' => [
                'submittedData' => $submittedData,
                'expectedData' => $expectedData,
                'savedAddress' => $savedAccountAddress,
                'addressType' => AddressTypeEntity::TYPE_SHIPPING
            ]
        ];
    }
}
