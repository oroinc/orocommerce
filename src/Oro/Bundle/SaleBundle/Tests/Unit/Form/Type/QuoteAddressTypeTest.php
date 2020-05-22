<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\AbstractAddressTypeTest;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Form\Type\QuoteAddressType;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteAddressTypeTest extends AbstractAddressTypeTest
{
    /** @var QuoteAddressType */
    protected $formType;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AddressFormatter */
    protected $addressFormatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QuoteAddressSecurityProvider */
    protected $quoteAddressSecurityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QuoteAddressManager */
    protected $quoteAddressManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Serializer */
    protected $serializer;

    /** @var TypedOrderAddressCollection|\PHPUnit\Framework\MockObject\MockObject */
    protected $addressCollection;

    protected function setUp(): void
    {
        $this->addressFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\AddressFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(function (AbstractAddress $item) {
                return $item->__toString();
            });

        $this->quoteAddressSecurityProvider = $this
            ->getMockBuilder('Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressCollection = $this->createMock(TypedOrderAddressCollection::class);

        $this->quoteAddressManager = $this->createMock(QuoteAddressManager::class);
        $this->quoteAddressManager->expects($this->any())
            ->method('getGroupedAddresses')
            ->willReturn($this->addressCollection);

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
        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($this->formType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return array_merge([new PreloadedExtension([$this->formType], [])], parent::getExtensions());
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit\Framework\MockObject\MockObject|OptionsResolver */
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

    public function testGetParent()
    {
        $this->assertEquals(AddressType::class, $this->formType->getParent());
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

        $this->addressCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->quoteAddressSecurityProvider->expects($this->once())->method('isManualEditGranted')->willReturn(true);

        $this->quoteAddressManager->expects($this->any())->method('updateFromAbstract')
            ->will(
                $this->returnCallback(
                    function (CustomerAddress $address = null, QuoteAddress $orderAddress = null) {
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
        list($country, $region) = $this->getValidCountryAndRegion();
        $countryWithoutRegion = new Country(self::COUNTRY_WITHOUT_REGION);

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
                    'country' => self::COUNTRY_WITHOUT_REGION,
                ],
                'expectedData' => (new QuoteAddress())->setCountry($countryWithoutRegion),
                'defaultData' => new QuoteAddress(),
            ],
            'customer address preselector' => [
                'isValid' => true,
                'submittedData' => [
                    'country' => self::COUNTRY_WITHOUT_REGION,
                    'customerAddress' => null,
                ],
                'expectedData' => (new QuoteAddress())->setCountry($countryWithoutRegion),
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
                    'region' => self::REGION_WITH_COUNTRY,
                    'postalCode' => 'AL',
                    'country' => self::COUNTRY_WITH_REGION,
                ],
                'expectedData' => (new QuoteAddress())
                    ->setLabel('Label_stripped')
                    ->setNamePrefix('NamePrefix_stripped')
                    ->setFirstName('FirstName_stripped')
                    ->setMiddleName('MiddleName_stripped')
                    ->setLastName('LastName_stripped')
                    ->setNameSuffix('NameSuffix_stripped')
                    ->setOrganization('Organization_stripped')
                    ->setStreet('Street_stripped')
                    ->setStreet2('Street2_stripped')
                    ->setCity('City_stripped')
                    ->setRegion($region)
                    ->setPostalCode('AL_stripped')
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
        $this->serializer->expects($this->any())->method('normalize')->willReturn(['a_1' => ['street' => 'street']]);

        $this->addressCollection->expects($this->once())
            ->method('toArray')
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
                    function (CustomerAddress $address) {
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
                    function (CustomerAddress $address = null, QuoteAddress $orderAddress = null) {
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
    public function submitWithoutPermissionProvider()
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
                    'customerAddress' => 'a_1',
                ],
                'expectedData' => null,
                'defaultData' => new QuoteAddress(),
                'formErrors' => ['customerAddress' => 'This value is not valid.'],
            ],
            'has identifier' => [
                'isValid' => true,
                'submittedData' => [
                    'customerAddress' => 'a_1',
                ],
                'expectedData' => (new QuoteAddress())
                    ->setCountry($country)
                    ->setStreet('Street'),
                'defaultData' => new QuoteAddress(),
                'formErrors' => ['customerAddress' => 1],
                'groupedAddresses' => [
                    'group_name' => [
                        'a_1' => (new CustomerAddress())
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
        $view->children = ['country' => new FormView(), 'city' => new FormView(), 'customerAddress' => new FormView()];

        $this->addressCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->quoteAddressSecurityProvider->expects($this->atLeastOnce())->method('isManualEditGranted')
            ->willReturn(false);

        $form = $this->factory->create(
            QuoteAddressType::class,
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

        $this->assertFalse($view->offsetGet('customerAddress')->vars['disabled']);
        $this->assertFalse($view->offsetGet('customerAddress')->vars['required']);
    }
}
