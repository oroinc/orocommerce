<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Form\Type\AbstractOrderAddressType;
use OroB2B\Bundle\OrderBundle\Manager\OrderAddressManager;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;

abstract class AbstractOrderAddressTypeTest extends AbstractAddressTypeTest
{
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

    protected function setUp()
    {
        parent::setUp();

        $this->addressFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\AddressFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressSecurityProvider = $this
            ->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressManager = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Manager\OrderAddressManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Serializer\Serializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->initFormType();
    }

    abstract protected function initFormType();

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
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

    abstract public function testGetName();

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
        $this->serializer->expects($this->any())->method('normalize')->willReturn(['a_1' => ['street' => 'street']]);
        $this->orderAddressManager->expects($this->once())->method('getGroupedAddresses')->willReturn([]);
        $this->orderAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(true);

        $this->orderAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (AccountAddress $address = null, OrderAddress $orderAddress = null) {
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
        $country = new Country('US');

        return [
            'empty data' => [
                'isValid' => false,
                'submittedData' => [],
                'expectedData' => new OrderAddress(),
                'defaultData' => new OrderAddress(),
                'formErrors' => ['country' => 'This value should not be blank.'],
            ],
            'invalid country' => [
                'isValid' => false,
                'submittedData' => [
                    'country' => 'XX',
                ],
                'expectedData' => new OrderAddress(),
                'defaultData' => new OrderAddress(),
                'formErrors' => ['country' => 'This value is not valid.'],
            ],
            'valid country only' => [
                'isValid' => true,
                'submittedData' => [
                    'country' => 'US',
                ],
                'expectedData' => (new OrderAddress())->setCountry(new Country('US')),
                'defaultData' => new OrderAddress(),
            ],
            'account address preselector' => [
                'isValid' => true,
                'submittedData' => [
                    'country' => 'US',
                    'accountAddress' => null,
                ],
                'expectedData' => (new OrderAddress())->setCountry(new Country('US')),
                'defaultData' => new OrderAddress(),
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
                    'organization' => 'Organization',
                    'street' => 'Street',
                    'street2' => 'Street2',
                    'city' => 'City',
                    'region' => 'US-AL',
                    'region_text' => 'Region Text',
                    'postalCode' => 'AL',
                    'country' => 'US',
                ],
                'expectedData' => (new OrderAddress())
                    ->setLabel('Label')
                    ->setNamePrefix('NamePrefix')
                    ->setFirstName('FirstName')
                    ->setMiddleName('MiddleName')
                    ->setLastName('LastName')
                    ->setNameSuffix('NameSuffix')
                    ->setOrganization('Organization')
                    ->setStreet('Street')
                    ->setStreet2('Street2')
                    ->setCity('City')
                    ->setRegion((new Region('US-AL'))->setCountry($country))
                    ->setRegionText('Region Text')
                    ->setPostalCode('AL')
                    ->setCountry($country),
                'defaultData' => new OrderAddress(),
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
     * @dataProvider submitWithPermissionProvider
     */
    public function testSubmitWithoutManualPermission(
        $isValid,
        $submittedData,
        $expectedData,
        $defaultData,
        array $formErrors = [],
        array $groupedAddresses = []
    ) {
        $this->serializer->expects($this->any())->method('normalize')->willReturn(['a_1' => ['street' => 'street']]);
        $this->orderAddressManager->expects($this->once())->method('getGroupedAddresses')
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
                    function (AccountAddress $address) {
                        $orderAddress = new OrderAddress();
                        $orderAddress->setCountry($address->getCountry());
                        $orderAddress->setStreet($address->getStreet());

                        return $orderAddress;
                    }
                )
            );

        $this->orderAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(false);

        $this->orderAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (AccountAddress $address = null, OrderAddress $orderAddress = null) {
                        if (!$orderAddress) {
                            $orderAddress = new OrderAddress();
                        }
                        return $orderAddress;
                    }
                )
            );

        $formOptions =  [
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
    public function submitWithPermissionProvider()
    {
        $country = new Country('US');

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
                    'accountAddress' => 'a_1',
                ],
                'expectedData' => null,
                'defaultData' => new OrderAddress(),
                'formErrors' => ['accountAddress' => 'This value is not valid.'],
            ],
            'has identifier' => [
                'isValid' => true,
                'submittedData' => [
                    'accountAddress' => 'a_1',
                ],
                'expectedData' => (new OrderAddress())
                    ->setCountry($country)
                    ->setStreet('Street'),
                'defaultData' => new OrderAddress(),
                'formErrors' => ['accountAddress' => 1],
                'groupedAddresses' => [
                    'group_name' => [
                        'a_1' => (new AccountAddress())
                            ->setCountry($country)
                            ->setStreet('Street'),
                    ],
                ],
            ],
        ];
    }

    public function testFinishView()
    {
        $view = new FormView();
        $view->children = ['country' => new FormView(), 'city' => new FormView(), 'accountAddress' => new FormView()];

        $this->orderAddressManager->expects($this->once())->method('getGroupedAddresses')->willReturn([]);
        $this->orderAddressSecurityProvider->expects($this->atLeastOnce())->method('isManualEditGranted')
            ->willReturn(false);

        $form = $this->factory->create(
            $this->formType,
            new OrderAddress(),
            ['addressType' => AddressTypeEntity::TYPE_SHIPPING, 'object' => $this->getEntity(), 'isEditEnabled' => true]
        );

        $this->formType->finishView($view, $form, ['addressType' => AddressTypeEntity::TYPE_SHIPPING]);

        foreach (['country', 'city'] as $childName) {
            $this->assertTrue($view->offsetGet($childName)->vars['disabled']);
        }

        $this->assertFalse($view->offsetGet('accountAddress')->vars['disabled']);
    }

    abstract protected function getEntity();
}
