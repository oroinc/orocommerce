<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\AbstractAddressTypeTest;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\AccountBundle\Entity\AccountAddress;
use Oro\Bundle\SaleBundle\Form\Type\QuoteAddressType;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;

class QuoteAddressTypeTest extends AbstractAddressTypeTest
{
    /** @var QuoteAddressType */
    protected $formType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AddressFormatter */
    protected $addressFormatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QuoteAddressSecurityProvider */
    protected $quoteAddressSecurityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QuoteAddressManager */
    protected $quoteAddressManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Serializer */
    protected $serializer;

    protected function setUp()
    {
        parent::setUp();

        $this->addressFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\AddressFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteAddressSecurityProvider = $this
            ->getMockBuilder('Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteAddressManager = $this->getMockBuilder('Oro\Bundle\SaleBundle\Model\QuoteAddressManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Serializer\Serializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new QuoteAddressType(
            $this->addressFormatter,
            $this->quoteAddressManager,
            $this->quoteAddressSecurityProvider,
            $this->serializer
        );

        $this->formType->setDataClass('Oro\Bundle\SaleBundle\Entity\QuoteAddress');
    }

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

    public function testGetName()
    {
        $this->assertEquals(QuoteAddressType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_address', $this->formType->getParent());
    }

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $formErrors
     *
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
        $this->quoteAddressManager->expects($this->once())->method('getGroupedAddresses')->willReturn([]);
        $this->quoteAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(true);

        $this->quoteAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (AccountAddress $address = null, QuoteAddress $orderAddress = null) {
                        if (!$orderAddress) {
                            $orderAddress = new QuoteAddress();
                        }
                        return $orderAddress;
                    }
                )
            );

        $formOptions = ['addressType' => AddressTypeEntity::TYPE_SHIPPING, 'quote' => new Quote()];

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
                'expectedData' => new QuoteAddress(),
                'defaultData' => new QuoteAddress(),
                'formErrors' => ['country' => 'This value should not be blank.'],
            ],
            'invalid country' => [
                'isValid' => false,
                'submittedData' => [
                    'country' => 'XX',
                ],
                'expectedData' => new QuoteAddress(),
                'defaultData' => new QuoteAddress(),
                'formErrors' => ['country' => 'This value is not valid.'],
            ],
            'valid country only' => [
                'isValid' => true,
                'submittedData' => [
                    'country' => 'US',
                ],
                'expectedData' => (new QuoteAddress())->setCountry(new Country('US')),
                'defaultData' => new QuoteAddress(),
            ],
            'account address preselector' => [
                'isValid' => true,
                'submittedData' => [
                    'country' => 'US',
                    'accountAddress' => null,
                ],
                'expectedData' => (new QuoteAddress())->setCountry(new Country('US')),
                'defaultData' => new QuoteAddress(),
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
                'expectedData' => (new QuoteAddress())
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
                'defaultData' => new QuoteAddress(),
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
        $this->quoteAddressManager->expects($this->once())->method('getGroupedAddresses')
            ->willReturn($groupedAddresses);
        $this->quoteAddressManager->expects($this->any())->method('getEntityByIdentifier')
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

        $this->quoteAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (AccountAddress $address) {
                        $quoteAddress = new QuoteAddress();
                        $quoteAddress->setCountry($address->getCountry());
                        $quoteAddress->setStreet($address->getStreet());

                        return $quoteAddress;
                    }
                )
            );

        $this->quoteAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(false);

        $this->quoteAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (AccountAddress $address = null, QuoteAddress $orderAddress = null) {
                        if (!$orderAddress) {
                            $orderAddress = new QuoteAddress();
                        }
                        return $orderAddress;
                    }
                )
            );

        $formOptions = ['addressType' => AddressTypeEntity::TYPE_SHIPPING, 'quote' => new Quote()];

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
                'defaultData' => new QuoteAddress(),
            ],
            'not valid identifier' => [
                'isValid' => false,
                'submittedData' => [
                    'accountAddress' => 'a_1',
                ],
                'expectedData' => null,
                'defaultData' => new QuoteAddress(),
                'formErrors' => ['accountAddress' => 'This value is not valid.'],
            ],
            'has identifier' => [
                'isValid' => true,
                'submittedData' => [
                    'accountAddress' => 'a_1',
                ],
                'expectedData' => (new QuoteAddress())
                    ->setCountry($country)
                    ->setStreet('Street'),
                'defaultData' => new QuoteAddress(),
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

        $this->quoteAddressManager->expects($this->once())->method('getGroupedAddresses')->willReturn([]);
        $this->quoteAddressSecurityProvider->expects($this->atLeastOnce())->method('isManualEditGranted')
            ->willReturn(false);

        $form = $this->factory->create(
            $this->formType,
            new QuoteAddress(),
            ['addressType' => AddressTypeEntity::TYPE_SHIPPING, 'quote' => new Quote()]
        );

        $this->formType->finishView($view, $form, ['addressType' => AddressTypeEntity::TYPE_SHIPPING]);

        foreach (['country', 'city'] as $childName) {
            $this->assertTrue($view->offsetGet($childName)->vars['disabled']);
            $this->assertFalse($view->offsetGet($childName)->vars['required']);

            $this->assertArrayNotHasKey('data-validation', $view->offsetGet($childName)->vars['attr']);
            $this->assertArrayNotHasKey('data-required', $view->offsetGet($childName)->vars['attr']);
            $this->assertArrayNotHasKey('label_attr', $view->offsetGet($childName)->vars);
        }

        $this->assertFalse($view->offsetGet('accountAddress')->vars['disabled']);
        $this->assertFalse($view->offsetGet('accountAddress')->vars['required']);
    }
}
