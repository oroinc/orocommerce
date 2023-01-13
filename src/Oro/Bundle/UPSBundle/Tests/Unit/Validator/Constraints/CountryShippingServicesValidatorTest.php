<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Validator\Constraints\CountryShippingServices;
use Oro\Bundle\UPSBundle\Validator\Constraints\CountryShippingServicesValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class CountryShippingServicesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): CountryShippingServicesValidator
    {
        return new CountryShippingServicesValidator();
    }

    public function testGetTargets()
    {
        $constraint = new CountryShippingServices();
        self::assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidate()
    {
        $country = $this->createMock(Country::class);
        $country->expects(self::any())
            ->method('__toString')
            ->willReturn('country');

        $wrongCountry = $this->createMock(Country::class);
        $wrongCountry->expects(self::any())
            ->method('__toString')
            ->willReturn('wrong country');

        $service1 = $this->createMock(ShippingService::class);
        $service1->expects(self::once())
            ->method('getCountry')
            ->willReturn($wrongCountry);
        $service1->expects(self::once())
            ->method('__toString')
            ->willReturn('service1');

        $service2 = $this->createMock(ShippingService::class);
        $service2->expects(self::once())
            ->method('getCountry')
            ->willReturn($wrongCountry);
        $service2->expects(self::once())
            ->method('__toString')
            ->willReturn('service2');

        $service3 = $this->createMock(ShippingService::class);
        $service3->expects(self::once())
            ->method('getCountry')
            ->willReturn($country);
        $service3->expects(self::never())
            ->method('__toString');

        $settings = $this->createMock(UPSTransport::class);
        $settings->expects(self::once())
            ->method('getUpsCountry')
            ->willReturn($country);
        $settings->expects(self::once())
            ->method('getApplicableShippingServices')
            ->willReturn([$service1, $service2, $service3]);

        $constraint = new CountryShippingServices();
        $this->validator->validate($settings, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameters([
                '%shipping_service%'         => 'service1',
                '%settings_country%'         => 'country',
                '%shipping_service_country%' => 'wrong country',
            ])
            ->atPath('property.path.applicableShippingServices')
            ->buildNextViolation($constraint->message)
            ->setParameters([
                '%shipping_service%'         => 'service2',
                '%settings_country%'         => 'country',
                '%shipping_service_country%' => 'wrong country',
            ])
            ->atPath('property.path.applicableShippingServices')
            ->assertRaised();
    }

    public function testValidateNotSettings()
    {
        $settings = $this->createMock(Transport::class);

        $constraint = new CountryShippingServices();
        $this->validator->validate($settings, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNoCountry()
    {
        $settings = $this->createMock(UPSTransport::class);
        $settings->expects(self::once())
            ->method('getUpsCountry')
            ->willReturn(null);

        $constraint = new CountryShippingServices();
        $this->validator->validate($settings, $constraint);

        $this->assertNoViolation();
    }
}
