<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\Type\AddressFormExtensionTestCase;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class PaymentMethodsConfigsRuleDestinationCollectionTypeTest extends AddressFormExtensionTestCase
{
    private PaymentMethodsConfigsRuleDestinationCollectionType $formType;

    protected function setUp(): void
    {
        $this->formType = new PaymentMethodsConfigsRuleDestinationCollectionType();
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new PreloadedExtension(
                [
                    $this->formType,
                    new PaymentMethodsConfigsRuleDestinationType(new AddressCountryAndRegionSubscriberStub())
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ]);
    }

    private function getDestination(
        string $countryCode,
        string $regionCode,
        array $postalCodes
    ): PaymentMethodsConfigsRuleDestination {
        $country = new Country($countryCode);

        $region = new Region($regionCode);
        $region->setCountry($country);
        $country->addRegion($region);

        $destination = new PaymentMethodsConfigsRuleDestination();
        $destination->setCountry($country);
        $destination->setRegion($region);

        foreach ($postalCodes as $code) {
            $postalCode = new PaymentMethodsConfigsRuleDestinationPostalCode();
            $postalCode->setName($code);

            $destination->addPostalCode($postalCode);
        }

        return $destination;
    }

    public function testSubmit()
    {
        $form = $this->factory->create(
            PaymentMethodsConfigsRuleDestinationCollectionType::class,
            [new PaymentMethodsConfigsRuleDestination(), new PaymentMethodsConfigsRuleDestination()],
            ['entry_options' => ['data_class' => PaymentMethodsConfigsRuleDestination::class]]
        );
        $form->submit([
            [
                'country' => self::COUNTRY_WITH_REGION,
                'region' => self::REGION_WITH_COUNTRY,
                'postalCodes' => 'code1, code2',
            ],
            [
                'country' => self::COUNTRY_WITHOUT_REGION,
            ]
        ]);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            [
                // first code not stripped, because form used model transformer that split string by comma
                // our extension applied on pre_submit, so all string stripped
                $this->getDestination(
                    self::COUNTRY_WITH_REGION,
                    self::REGION_WITH_COUNTRY,
                    ['code1', 'code2_stripped']
                ),
                (new PaymentMethodsConfigsRuleDestination())
                    ->setCountry(new Country(self::COUNTRY_WITHOUT_REGION)),
            ],
            $form->getData()
        );
    }

    public function testGetParent()
    {
        self::assertSame(CollectionType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        self::assertSame('oro_payment_methods_configs_rule_destination_collection', $this->formType->getBlockPrefix());
    }
}
