<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use OroB2B\Bundle\CheckoutBundle\Form\Type\CheckoutAddressType;
use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\EntityConfigBundle\Form\Type\ChoiceType;
use Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use OroB2B\Bundle\CheckoutBundle\Form\Type\CountryType;
use OroB2B\Bundle\CheckoutBundle\Form\Type\RegionType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
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
        return array_merge([$this->getValidatorExtension(true)], [
            new PreloadedExtension(
                [
                    'oro_address' => new AddressType(new AddressCountryAndRegionSubscriberStub()),
                    'orob2b_country' => new CountryType(),
                    'genemu_jqueryselect2_choice' => new Select2Type('choice'),
                    'translatable_entity' => $translatableEntity,
                    'orob2b_region' => new RegionType(),
                ],
                ['form' => [new RandomIdExtension()]]
            )
        ]);
    }
}
