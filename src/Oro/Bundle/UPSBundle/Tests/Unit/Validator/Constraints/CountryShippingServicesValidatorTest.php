<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Validator\Constraints\CountryShippingServicesConstraint;
use Oro\Bundle\UPSBundle\Validator\Constraints\CountryShippingServicesValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class CountryShippingServicesValidatorTest extends \PHPUnit\Framework\TestCase
{
    const ALIAS = 'oro_ups_country_shipping_services_validator';

    /**
     * @internal
     */
    const VIOLATION_PATH = 'applicableShippingServices';

    /**
     * @var CountryShippingServicesConstraint|\PHPUnit\Framework\MockObject\MockObject
     */
    private $constraint;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var CountryShippingServicesValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->constraint = $this->createMock(CountryShippingServicesConstraint::class);

        $this->validator = new CountryShippingServicesValidator();

        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator->initialize($this->context);
    }

    public function testValidate()
    {
        $country = $this->createMock(Country::class);

        $country
            ->method('__toString')
            ->willReturn('country');

        $wrongCountry = $this->createMock(Country::class);

        $wrongCountry
            ->method('__toString')
            ->willReturn('wrong country');

        $settings = $this->createSettingsMock();

        $settings->expects(static::once())
            ->method('getUpsCountry')
            ->willReturn($country);

        $service1 = $this->createShippingServiceMock();

        $service1->expects(static::once())
            ->method('getCountry')
            ->willReturn($wrongCountry);

        $service1->expects(static::once())
            ->method('__toString')
            ->willReturn('service1');

        $service2 = $this->createShippingServiceMock();

        $service2->expects(static::once())
            ->method('getCountry')
            ->willReturn($wrongCountry);

        $service2->expects(static::once())
            ->method('__toString')
            ->willReturn('service2');

        $service3 = $this->createShippingServiceMock();

        $service3->expects(static::once())
            ->method('getCountry')
            ->willReturn($country);

        $service3->expects(static::never())
            ->method('__toString');

        $settings->expects(static::once())
            ->method('getApplicableShippingServices')
            ->willReturn([
                $service1,
                $service2,
                $service3,
            ]);

        $builder1 = $this->createMock(ConstraintViolationBuilderInterface::class);

        $builder1->expects(static::once())
            ->method('atPath')
            ->with('applicableShippingServices')
            ->willReturn($builder1);

        $builder1->expects(static::once())
            ->method('addViolation');

        $this->context->expects(static::at(0))
            ->method('buildViolation')
            ->with('oro.ups.settings.shipping_service.wrong_country.message', [
                '%shipping_service%' => 'service1',
                '%settings_country%' => 'country',
                '%shipping_service_country%' => 'wrong country',
            ])
            ->willReturn($builder1);

        $builder2 = $this->createMock(ConstraintViolationBuilderInterface::class);

        $builder2->expects(static::once())
            ->method('atPath')
            ->with('applicableShippingServices')
            ->willReturn($builder2);

        $builder2->expects(static::once())
            ->method('addViolation');

        $this->context->expects(static::at(1))
            ->method('buildViolation')
            ->with('oro.ups.settings.shipping_service.wrong_country.message', [
                '%shipping_service%' => 'service2',
                '%settings_country%' => 'country',
                '%shipping_service_country%' => 'wrong country',
            ])
            ->willReturn($builder2);

        $this->validator->validate($settings, $this->constraint);
    }

    public function testValidateNotSettings()
    {
        $settings = $this->createTransportMock();

        $this->context->expects(static::never())
            ->method('buildViolation');

        $this->validator->validate($settings, $this->constraint);
    }

    public function testValidateNoCountry()
    {
        $settings = $this->createSettingsMock();

        $settings->expects(static::once())
            ->method('getUpsCountry')
            ->willReturn(null);

        $this->context->expects(static::never())
            ->method('buildViolation');

        $this->validator->validate($settings, $this->constraint);
    }

    /**
     * @return UPSTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSettingsMock()
    {
        return $this->createMock(UPSTransport::class);
    }

    /**
     * @return ShippingService|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createShippingServiceMock()
    {
        return $this->createMock(ShippingService::class);
    }

    /**
     * @return Transport|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createTransportMock()
    {
        return $this->createMock(Transport::class);
    }
}
