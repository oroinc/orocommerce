<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class PaymentMethodsConfigsRuleDestinationCollectionTypeTest extends AddressFormExtensionTestCase
{
    use EntityTrait;

    /**
     * @var PaymentMethodsConfigsRuleDestinationCollectionType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new PaymentMethodsConfigsRuleDestinationCollectionType();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|PaymentMethodsConfigsRuleDestination[] $existing
     * @param array $submitted
     * @param array|PaymentMethodsConfigsRuleDestination[] $expected
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'options' => [
                'data_class' => PaymentMethodsConfigsRuleDestination::class
            ]
        ];

        $form = $this->factory->create($this->type, $existing, $options);
        $form->submit($submitted);

        static::assertTrue($form->isValid());
        static::assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'test' => [
                'existing' => [
                    new PaymentMethodsConfigsRuleDestination(),
                    new PaymentMethodsConfigsRuleDestination(),
                ],
                'submitted' => [
                    [
                        'country' => self::COUNTRY_WITH_REGION,
                        'region' => self::REGION_WITH_COUNTRY,
                        'postalCodes' => 'code1, code2',
                    ],
                    [
                        'country' => self::COUNTRY_WITHOUT_REGION,
                    ]
                ],
                'expected' => [
                    // first code not stripped, because form used model transformer that split string by comma
                    // our extension applied on pre_submit, so all string stripped
                    $this->getDestination(
                        self::COUNTRY_WITH_REGION,
                        self::REGION_WITH_COUNTRY,
                        ['code1', 'code2_stripped']
                    ),
                    (new PaymentMethodsConfigsRuleDestination())
                        ->setCountry(new Country(self::COUNTRY_WITHOUT_REGION)),
                ]
            ]
        ];
    }

    /**
     * @param string $countryCode
     * @param string $regionCode
     * @param array $postalCodes
     * @return PaymentMethodsConfigsRuleDestination
     */
    protected function getDestination($countryCode, $regionCode, array $postalCodes)
    {
        $country = new Country($countryCode);

        $region = new Region($regionCode);
        $region->setCountry($country);
        $country->addRegion($region);

        $destination = new PaymentMethodsConfigsRuleDestination();
        $destination->setCountry($country)
            ->setRegion($region);

        foreach ($postalCodes as $code) {
            $postalCode = new PaymentMethodsConfigsRuleDestinationPostalCode();
            $postalCode->setName($code);

            $destination->addPostalCode($postalCode);
        }

        return $destination;
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $translatableEntity = $this->getTranslatableEntity();

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    PaymentMethodsConfigsRuleDestinationType::NAME => new PaymentMethodsConfigsRuleDestinationType(
                        new AddressCountryAndRegionSubscriberStub()
                    ),
                    'oro_country' => new CountryType(),
                    'oro_region' => new RegionType(),
                    'oro_select2_translatable_entity' => new Select2Type(
                        'translatable_entity',
                        'oro_select2_translatable_entity'
                    ),
                    'translatable_entity' => $translatableEntity,
                ],
                ['form' => [
                    new StripTagsExtensionStub($this->createMock(HtmlTagHelper::class)),
                ]]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetName()
    {
        static::assertSame(PaymentMethodsConfigsRuleDestinationCollectionType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        static::assertSame(CollectionType::NAME, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame(PaymentMethodsConfigsRuleDestinationCollectionType::NAME, $this->type->getBlockPrefix());
    }
}
