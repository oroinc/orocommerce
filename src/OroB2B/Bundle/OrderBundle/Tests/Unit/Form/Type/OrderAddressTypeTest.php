<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderAddressType;
use OroB2B\Bundle\OrderBundle\Model\OrderAddressManager;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\OrderBundle\Tests\Unit\Stub\AddressCountryAndRegionSubscriberStub;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolation;

class OrderAddressTypeTest extends FormIntegrationTestCase
{
    /** @var OrderAddressType */
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

        $this->orderAddressManager = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Model\OrderAddressManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Serializer\Serializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new OrderAddressType(
            $this->addressFormatter,
            $this->orderAddressManager,
            $this->orderAddressSecurityProvider,
            $this->serializer
        );
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
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
        $this->assertEquals(OrderAddressType::NAME, $this->formType->getName());
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

        $this->checkForm($isValid, $submittedData, $expectedData, $defaultData, $formErrors);
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

        $this->checkForm($isValid, $submittedData, $expectedData, $defaultData, $formErrors);
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

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $formErrors
     */
    protected function checkForm($isValid, $submittedData, $expectedData, $defaultData, $formErrors)
    {
        $form = $this->factory->create(
            $this->formType,
            $defaultData,
            ['addressType' => AddressTypeEntity::TYPE_SHIPPING, 'order' => new Order()]
        );
        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());

        if ($form->getErrors(true)->count()) {
            $this->assertNotEmpty($formErrors);
        }

        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $this->assertArrayHasKey($error->getOrigin()->getName(), $formErrors);

            /** @var ConstraintViolation $violation */
            $violation = $error->getCause();
            $this->assertEquals(
                $formErrors[$error->getOrigin()->getName()],
                $error->getMessage(),
                sprintf('Failed path: %s', $violation->getPropertyPath())
            );
        }
        $this->assertEquals($expectedData, $form->getData());

        $this->assertTrue($form->has('accountAddress'));
        $this->assertTrue($form->get('accountAddress')->getConfig()->hasOption('attr'));
        $this->assertArrayHasKey('data-addresses', $form->get('accountAddress')->getConfig()->getOption('attr'));
        $this->assertInternalType(
            'string',
            $form->get('accountAddress')->getConfig()->getOption('attr')['data-addresses']
        );
        $this->assertInternalType(
            'array',
            json_decode($form->get('accountAddress')->getConfig()->getOption('attr')['data-addresses'], true)
        );
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
            ['addressType' => AddressTypeEntity::TYPE_SHIPPING, 'order' => new Order()]
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

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $country = new Country('US');
        $choices = [
            'OroAddressBundle:Country' => ['US' => $country],
            'OroAddressBundle:Region' => ['US-AL' => (new Region('US-AL'))->setCountry($country)],
        ];

        $translatableEntity->expects($this->any())->method('setDefaultOptions')->will(
            $this->returnCallback(
                function (OptionsResolver $resolver) use ($choices) {
                    $choiceList = function (Options $options) use ($choices) {
                        $className = $options->offsetGet('class');
                        if (array_key_exists($className, $choices)) {
                            return new ArrayChoiceList(
                                $choices[$className],
                                function ($item) {
                                    if ($item instanceof Country) {
                                        return $item->getIso2Code();
                                    }

                                    if ($item instanceof Region) {
                                        return $item->getCombinedCode();
                                    }

                                    return $item . uniqid('form', true);
                                }
                            );
                        }

                        return new ArrayChoiceList([]);
                    };

                    $resolver->setDefault('choice_list', $choiceList);
                }
            )
        );

        return [
            new PreloadedExtension(
                [
                    'oro_address' => new AddressType(new AddressCountryAndRegionSubscriberStub()),
                    'oro_country' => new CountryType(),
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'genemu_jqueryselect2_choice' => new Select2Type('choice'),
                    'translatable_entity' => $translatableEntity,
                    'oro_region' => new RegionType(),
                ],
                ['form' => [new RandomIdExtension()]]
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
