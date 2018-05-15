<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\AbstractOrderAddressType;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractOrderAddressTypeTest extends AbstractAddressTypeTest
{
    const ORGANIZATION = 'test organization';
    const CITY = 'test city';
    const STREET = 'test street';
    const POSTAL_CODE = '1234567';

    /** @var AbstractOrderAddressType */
    protected $formType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AddressFormatter */
    protected $addressFormatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderAddressSecurityProvider */
    protected $orderAddressSecurityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderAddressManager */
    protected $orderAddressManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Serializer */
    protected $serializer;

    /** @var TypedOrderAddressCollection|\PHPUnit_Framework_MockObject_MockObject */
    protected $addressCollection;

    protected function setUp()
    {
        $this->addressFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\AddressFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(function (AbstractAddress $item) {
                return $item->__toString();
            });

        $this->orderAddressSecurityProvider = $this
            ->getMockBuilder('Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressCollection = $this->createMock(TypedOrderAddressCollection::class);

        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);
        $this->orderAddressManager->expects($this->any())
            ->method('getGroupedAddresses')
            ->willReturn($this->addressCollection);

        $this->serializer = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Serializer\Serializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->initFormType();
        parent::setUp();
    }

    abstract protected function initFormType();

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())->method('setDefaults')->with($this->isType('array'))
            ->will($this->returnSelf());
        $resolver->expects($this->once())->method('setRequired')->with($this->isType('array'))
            ->will($this->returnSelf());
        $resolver->expects($this->once())->method('setAllowedValues')
            ->with($this->isType('string'), $this->isType('array'))->will($this->returnSelf());
        $resolver->expects($this->once())->method('setAllowedTypes')
            ->with($this->isType('string'), $this->isType('string'))->will($this->returnSelf());

        $this->formType->configureOptions($resolver);
    }

    abstract public function testGetParent();

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $formErrors
     * @dataProvider submitProvider
     */
    public function testSubmitWithManualPermission(
        $isValid,
        $submittedData,
        $expectedData,
        $defaultData,
        array $formErrors = []
    ) {
        $this->orderAddressManager->expects($this->once())->method('getGroupedAddresses')->willReturn([]);
        $this->serializer->expects($this->any())->method('normalize')->willReturn(['a_1' => ['street' => 'street']]);

        $this->addressCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->orderAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(true);

        $this->orderAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (CustomerAddress $address = null, OrderAddress $orderAddress = null) {
                        if (!$orderAddress) {
                            $orderAddress = new OrderAddress();
                        }

                        return $orderAddress;
                    }
                )
            );

        $formOptions = [
            'addressType' => AddressTypeEntity::TYPE_SHIPPING,
            'object' => $this->getEntity(),
            'isEditEnabled' => true,
        ];

        $this->checkForm($isValid, $submittedData, $expectedData, $defaultData, $formErrors, $formOptions);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider()
    {
        list($country, $region) = $this->getValidCountryAndRegion();
        $emptyAddress = new OrderAddress();
        $validAddress = $this->getValidAddress();

        return [
            'empty data' => [
                'isValid' => false,
                'submittedData' => [],
                'expectedData' => $emptyAddress,
                'defaultData' => $emptyAddress,
                'formErrors' => [
                    'city' => 'This value should not be blank.',
                    'street' => 'This value should not be blank.',
                    'country' => 'This value should not be blank.',
                    'postalCode' => 'This value should not be blank.',
                    'firstName' => 'oro.address.validation.invalid_first_name_field',
                    'lastName' => 'oro.address.validation.invalid_last_name_field',
                    'organization' => 'oro.address.validation.invalid_organization_field',
                ],
            ],
            'invalid country' => [
                'isValid' => false,
                'submittedData' => [
                    'country' => 'XX',
                    'organization' => static::ORGANIZATION,
                    'city' => static::CITY,
                    'street' => static::STREET,
                    'postalCode' => static::POSTAL_CODE,
                ],
                'expectedData' => $this->getValidAddress()->setCountry(null)->setRegion(null),
                'defaultData' => $emptyAddress,
                'formErrors' => ['country' => 'This value is not valid.'],
            ],
            'empty organization' => [
                'isValid' => false,
                'submittedData' => [
                    'country' => static::COUNTRY_WITH_REGION,
                    'region' => static::REGION_WITH_COUNTRY,
                    'city' => static::CITY,
                    'street' => static::STREET,
                    'postalCode' => static::POSTAL_CODE,
                ],
                'expectedData' => $this->getValidAddress()->setOrganization(null),
                'defaultData' => $emptyAddress,
                'formErrors' => [
                    'firstName' => 'oro.address.validation.invalid_first_name_field',
                    'lastName' => 'oro.address.validation.invalid_last_name_field',
                    'organization' => 'oro.address.validation.invalid_organization_field',
                ],
            ],
            'valid data' => [
                'isValid' => true,
                'submittedData' => [
                    'country' => static::COUNTRY_WITH_REGION,
                    'region' => static::REGION_WITH_COUNTRY,
                    'organization' => static::ORGANIZATION,
                    'city' => static::CITY,
                    'street' => static::STREET,
                    'postalCode' => static::POSTAL_CODE,
                ],
                'expectedData' => $validAddress,
                'defaultData' => $emptyAddress,
            ],
            'customer address preselector' => [
                'isValid' => true,
                'submittedData' => [
                    'country' => static::COUNTRY_WITH_REGION,
                    'region' => static::REGION_WITH_COUNTRY,
                    'organization' => static::ORGANIZATION,
                    'city' => static::CITY,
                    'street' => static::STREET,
                    'postalCode' => static::POSTAL_CODE,
                    'customerAddress' => null,
                ],
                'expectedData' => $validAddress,
                'defaultData' => $emptyAddress,
            ],
            'valid full' => [
                'isValid' => true,
                'submittedData' => [
                    'label' => 'Label',
                    'namePrefix' => 'NamePrefix',
                    'firstName' => 'FirstName',
                    'middleName' => 'MiddleName',
                    'lastName' => 'LastName',
                    'nameSuffix' => 'NameSuffix',
                    'organization' => static::ORGANIZATION,
                    'street' => 'Street',
                    'street2' => 'Street2',
                    'city' => 'City',
                    'region_text' => 'Region Text',
                    'postalCode' => 'AL',
                    'country' => static::COUNTRY_WITH_REGION,
                    'region' => static::REGION_WITH_COUNTRY,
                ],
                'expectedData' => (new OrderAddress())
                    ->setLabel('Label_stripped')
                    ->setNamePrefix('NamePrefix_stripped')
                    ->setFirstName('FirstName_stripped')
                    ->setMiddleName('MiddleName_stripped')
                    ->setLastName('LastName_stripped')
                    ->setNameSuffix('NameSuffix_stripped')
                    ->setOrganization(static::ORGANIZATION . '_stripped')
                    ->setStreet('Street_stripped')
                    ->setStreet2('Street2_stripped')
                    ->setCity('City_stripped')
                    ->setRegion($region)
                    ->setRegionText('Region Text')
                    ->setPostalCode('AL_stripped')
                    ->setCountry($country),
                'defaultData' => $emptyAddress,
            ],
        ];
    }

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $formErrors
     * @param array $groupedAddresses
     * @dataProvider submitWithoutPermissionProvider
     */
    public function testSubmitWithoutManualPermission(
        $isValid,
        $submittedData,
        $expectedData,
        $defaultData,
        array $formErrors = [],
        array $groupedAddresses = []
    ) {
        $this->serializer->expects($this->any())->method('normalize')->willReturn(
            ['a_1' => ['street' => 'street', 'organization' => static::ORGANIZATION]]
        );
        $this->orderAddressManager->expects($this->once())->method('getGroupedAddresses')
            ->willReturn($groupedAddresses);

        $this->addressCollection->expects($this->once())
            ->method('toArray')
            ->willReturn($groupedAddresses);

        $this->orderAddressManager->expects($this->any())->method('getEntityByIdentifier')
            ->will(
                $this->returnCallback(
                    function ($identifier) use ($groupedAddresses) {
                        foreach ($groupedAddresses as $groupedAddressesGroup) {
                            if (array_key_exists($identifier, $groupedAddressesGroup)) {
                                return $groupedAddressesGroup[$identifier];
                            }
                        }

                        return null;
                    }
                )
            );

        $this->orderAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (CustomerAddress $address) {
                        $orderAddress = new OrderAddress();
                        $orderAddress->setCountry($address->getCountry())
                            ->setRegion($address->getRegion())
                            ->setOrganization(static::ORGANIZATION)
                            ->setStreet($address->getStreet())
                            ->setCity($address->getCity())
                            ->setPostalCode($address->getPostalCode());

                        return $orderAddress;
                    }
                )
            );

        $this->orderAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(false);

        $this->orderAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (CustomerAddress $address = null, OrderAddress $orderAddress = null) {
                        if (!$orderAddress) {
                            $orderAddress = new OrderAddress();
                        }

                        return $orderAddress;
                    }
                )
            );

        $formOptions = [
            'addressType' => AddressTypeEntity::TYPE_SHIPPING,
            'object' => $this->getEntity(),
            'isEditEnabled' => true,
        ];

        $this->checkForm($isValid, $submittedData, $expectedData, $defaultData, $formErrors, $formOptions);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitWithoutPermissionProvider()
    {
        list($country, $region) = $this->getValidCountryAndRegion();

        return [
            'empty data' => [
                'isValid' => true,
                'submittedData' => [],
                'expectedData' => null,
                'defaultData' => new OrderAddress(),
            ],
            'not valid identifier' => [
                'isValid' => false,
                'submittedData' => [
                    'customerAddress' => 'a_1',
                ],
                'expectedData' => null,
                'defaultData' => new OrderAddress(),
                'formErrors' => ['customerAddress' => 'This value is not valid.'],
            ],
            'has identifier' => [
                'isValid' => true,
                'submittedData' => [
                    'customerAddress' => 'a_1',
                ],
                'expectedData' => $this->getValidAddress(false),
                'defaultData' => new OrderAddress(),
                'formErrors' => ['customerAddress' => 1],
                'groupedAddresses' => [
                    'group_name' => [
                        'a_1' => (new CustomerAddress())
                            ->setCountry($country)
                            ->setRegion($region)
                            ->setStreet(static::STREET)
                            ->setCity(static::CITY)
                            ->setPostalCode(static::POSTAL_CODE)
                            ->setOrganization(static::ORGANIZATION)
                        ,
                    ],
                ],
            ],
        ];
    }

    public function testFinishView()
    {
        $view = new FormView();
        $view->children = ['country' => new FormView(), 'city' => new FormView(), 'customerAddress' => new FormView()];

        $this->addressCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->orderAddressSecurityProvider->expects($this->atLeastOnce())->method('isManualEditGranted')
            ->willReturn(false);

        $form = $this->factory->create(
            get_class($this->formType),
            new OrderAddress(),
            ['addressType' => AddressTypeEntity::TYPE_SHIPPING, 'object' => $this->getEntity(), 'isEditEnabled' => true]
        );

        $this->formType->finishView($view, $form, ['addressType' => AddressTypeEntity::TYPE_SHIPPING]);

        foreach (['country', 'city'] as $childName) {
            $this->assertTrue($view->offsetGet($childName)->vars['disabled']);
        }

        $this->assertFalse($view->offsetGet('customerAddress')->vars['disabled']);
    }

    abstract protected function getEntity();

    /**
     * @param bool $isStripped
     *
     * @return OrderAddress
     */
    protected function getValidAddress($isStripped = true)
    {
        $validAddress = new OrderAddress();

        list($country, $region) = $this->getValidCountryAndRegion();

        return $validAddress->setOrganization($isStripped ? static::ORGANIZATION . '_stripped' : static::ORGANIZATION)
            ->setCountry($country)
            ->setRegion($region)
            ->setCity($isStripped ? static::CITY . '_stripped' : static::CITY)
            ->setStreet($isStripped ? static::STREET . '_stripped' : static::STREET)
            ->setPostalCode($isStripped ? static::POSTAL_CODE . '_stripped' : static::POSTAL_CODE);
    }
}
