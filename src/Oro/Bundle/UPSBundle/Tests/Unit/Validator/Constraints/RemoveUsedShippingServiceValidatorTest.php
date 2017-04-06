<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingServiceConstraint;
use Oro\Bundle\UPSBundle\Validator\Constraints\RemoveUsedShippingServiceValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class RemoveUsedShippingServiceValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IntegrationShippingMethodFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integrationShippingMethodFactory;

    /**
     * @var ShippingMethodValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingMethodValidator;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var RemoveUsedShippingServiceValidator
     */
    private $validator;

    /**
     * @var RemoveUsedShippingServiceConstraint
     */
    private $constraint;

    protected function setUp()
    {
        $this->integrationShippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $this->shippingMethodValidator = $this->createMock(ShippingMethodValidatorInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new RemoveUsedShippingServiceValidator(
            $this->integrationShippingMethodFactory,
            $this->shippingMethodValidator
        );
        $this->validator->initialize($this->context);

        $this->constraint = new RemoveUsedShippingServiceConstraint();
    }

    public function testValidateNotUPSSettings()
    {
        $this->context->expects(static::never())
            ->method('buildViolation');
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateNoChannel()
    {
        $transport = $this->createUPSSettingsMock();
        $this->context->expects(static::never())
            ->method('buildViolation');
        $this->validator->validate($transport, $this->constraint);
    }

    public function testValidateNoErrors()
    {
        $channel = $this->createMock(Channel::class);

        $transport = $this->createUPSSettingsMock();
        $transport->expects(static::any())
            ->method('getChannel')
            ->willReturn($channel);

        $upsShippingMethod = $this->createMock(UPSShippingMethod::class);

        $this->integrationShippingMethodFactory->expects(static::once())
            ->method('create')
            ->with($channel)
            ->willReturn($upsShippingMethod);

        $errorsCollection = $this->createMock(
            Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface::class
        );
        $errorsCollection->expects(static::once())
            ->method('isEmpty')
            ->willReturn(true);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);
        $result->expects(static::any())
            ->method('getErrors')
            ->willReturn($errorsCollection);

        $this->shippingMethodValidator->expects(static::once())
            ->method('validate')
            ->with($upsShippingMethod)
            ->willReturn($result);

        $this->context->expects(static::never())
            ->method('buildViolation');

        $this->validator->validate($transport, $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $channel = $this->createMock(Channel::class);

        $transport = $this->createUPSSettingsMock();
        $transport->expects(static::any())
            ->method('getChannel')
            ->willReturn($channel);

        $upsShippingMethod = $this->createMock(UPSShippingMethod::class);

        $this->integrationShippingMethodFactory->expects(static::once())
            ->method('create')
            ->with($channel)
            ->willReturn($upsShippingMethod);

        $errorMessage = 'Error message about types';

        $error = $this->createErrorMock();
        $error->expects(static::once())
            ->method('getMessage')
            ->willReturn($errorMessage);

        $errorsCollection = new Error\Collection\Doctrine\DoctrineShippingMethodValidatorResultErrorCollection();
        $errorsCollection = $errorsCollection->createCommonBuilder()
            ->addError($error)->getCollection();

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);
        $result->expects(static::any())
            ->method('getErrors')
            ->willReturn($errorsCollection);

        $this->shippingMethodValidator->expects(static::once())
            ->method('validate')
            ->with($upsShippingMethod)
            ->willReturn($result);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects(static::once())
            ->method('setTranslationDomain')
            ->willReturn($violationBuilder);
        $violationBuilder->expects(static::once())
            ->method('atPath')
            ->with('applicableShippingServices')
            ->willReturn($violationBuilder);

        $this->context->expects(static::once())
            ->method('buildViolation')
            ->with($errorMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($transport, $this->constraint);
    }

    /**
     * @return UPSTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createUPSSettingsMock()
    {
        return $this->createMock(UPSTransport::class);
    }

    /**
     * @return Error\ShippingMethodValidatorResultErrorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createErrorMock()
    {
        return $this->createMock(Error\ShippingMethodValidatorResultErrorInterface::class);
    }
}
