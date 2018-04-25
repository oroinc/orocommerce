<?php

namespace Oro\Component\Testing\Unit;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AddressFormExtensionTestCase extends FormIntegrationTestCase
{
    const COUNTRY_WITHOUT_REGION = 'US';
    const COUNTRY_WITH_REGION = 'RO';
    const REGION_WITH_COUNTRY = 'RO-MS';

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $typeGuesser = $this->createMock(
            'Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser'
        );

        return [
            new PreloadedExtension(
                [
                    AddressType::class => new AddressType(new AddressCountryAndRegionSubscriberStub()),
                    CountryType::class => new CountryType(),
                    TranslatableEntityType::class => $this->getTranslatableEntity(),
                    RegionType::class => new RegionType(),
                ],
                [
                    FormType::class => [
                        new AdditionalAttrExtension(),
                        new StripTagsExtensionStub($this->createMock(HtmlTagHelper::class)),
                    ],
                ],
                $typeGuesser
            )
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTranslatableEntity()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['configureOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        list($country, $region) = $this->getValidCountryAndRegion();
        $countryCA = new Country('CA');

        $choices = [
            'OroAddressBundle:Country' => [
                'CA' => $countryCA,
                self::COUNTRY_WITH_REGION => $country,
                self::COUNTRY_WITHOUT_REGION => new Country(self::COUNTRY_WITHOUT_REGION),
            ],
            'OroAddressBundle:Region' => [
                self::REGION_WITH_COUNTRY => $region,
                'CA-QC' => (new Region('CA-QC'))->setCountry($countryCA),
            ],
        ];

        $translatableEntity->expects($this->any())->method('configureOptions')->will(
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

                    // TODO: remove 'choice_list' in scope of BAP-16967
                    $resolver->setDefault('choice_list', $choiceList);
                }
            )
        );

        return $translatableEntity;
    }

    /**
     * @return array
     */
    protected function getValidCountryAndRegion()
    {
        $country = new Country(self::COUNTRY_WITH_REGION);
        $region = new Region(self::REGION_WITH_COUNTRY);
        $region->setCountry($country);
        $country->addRegion($region);

        return [$country, $region];
    }
}
