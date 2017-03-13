<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;
use Oro\Bundle\ShippingBundle\Method\Factory\MethodTypeChangeEventFactoryInterface;
use Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingService;
use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingServiceValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RemoveUsedShippingServiceValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $identifierGenerator;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var ShippingServiceRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serviceRepository;

    /**
     * @var MethodTypeChangeEventFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $typeChangeEventFactory;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var RemoveUsedShippingServiceValidator
     */
    private $validator;

    /**
     * @var RemoveUsedShippingService
     */
    private $constraint;

    /**
     * @var Country
     */
    private $country;

    protected function setUp()
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->serviceRepository = $this->createMock(ShippingServiceRepository::class);
        $this->typeChangeEventFactory = $this->createMock(MethodTypeChangeEventFactoryInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new RemoveUsedShippingServiceValidator(
            $this->identifierGenerator,
            $this->dispatcher,
            $this->serviceRepository,
            $this->typeChangeEventFactory
        );
        $this->validator->initialize($this->context);

        $this->constraint = new RemoveUsedShippingService();
        $this->country = new Country('US');
    }

    public function testValidateNoErrors()
    {
        $selectedServiceCodes = ['11', '2'];

        $transport = new UPSTransport();
        $transport->setCountry($this->country)
            ->setChannel(new Channel());

        $event = new MethodTypeChangeEvent($selectedServiceCodes, 'id');

        $this->typeChangeEventFactory->expects(static::once())
            ->method('create')
            ->willReturn($event);

        $this->context->expects(static::never())
            ->method('buildViolation');

        $this->validator->validate($transport, $this->constraint);

        static::assertFalse($event->hasErrors());
    }

    public function testValidateWithErrors()
    {
        $selectedServiceCodes = ['11', '2'];

        $transport = new UPSTransport();
        $transport->setCountry($this->country)
            ->setChannel(new Channel());

        $event = new MethodTypeChangeEvent($selectedServiceCodes, 'id');
        $event->addErrorType('3');
        $event->addErrorType('4');

        $this->typeChangeEventFactory->expects(static::once())
            ->method('create')
            ->willReturn($event);

        $this->serviceRepository->expects(static::once())
            ->method('getShippingServicesByCountry')
            ->willReturn([
                $this->getShippingService('3', 'name3')
            ]);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects(static::once())
            ->method('setParameter')
            ->willReturn($violationBuilder);
        $violationBuilder->expects(static::once())
            ->method('setTranslationDomain')
            ->willReturn($violationBuilder);
        $violationBuilder->expects(static::once())
            ->method('atPath')
            ->willReturn($violationBuilder);

        $this->context->expects(static::once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->validator->validate($transport, $this->constraint);
    }

    /**
     * @param string $code
     * @param string $description
     *
     * @return ShippingService
     */
    private function getShippingService($code, $description)
    {
        $service = new ShippingService();

        $service->setCode($code)
            ->setDescription($description);

        return $service;
    }
}
