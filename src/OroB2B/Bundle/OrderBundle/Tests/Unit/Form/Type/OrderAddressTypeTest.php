<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolation;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderAddressType;
use OroB2B\Bundle\OrderBundle\Tests\Unit\Stub\AddressCountryAndRegionSubscriberStub;

class OrderAddressTypeTest extends FormIntegrationTestCase
{
    /** @var OrderAddressType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OrderAddressType();
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
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => 'OroB2B\Bundle\OrderBundle\Entity\OrderAddress']);

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
    public function testSubmit($isValid, $submittedData, $expectedData, $defaultData, array $formErrors = [])
    {
        $form = $this->factory->create($this->formType, $defaultData, []);
        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());

        $this->assertCount($form->getErrors(true)->count(), $formErrors);
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
                'formErrors' => [],
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
                'formErrors' => [],
            ],
        ];
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
