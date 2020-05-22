<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

// @codingStandardsIgnoreStart
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Doctrine\DoctrineShippingMethodValidatorResultErrorCollection;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\ShippingMethodValidatorResultErrorInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UpdateIntegrationValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

// @codingStandardsIgnoreEnd

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateIntegrationValidatorTest extends TestCase
{
    /**
     * @var IntegrationShippingMethodFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingMethodFactory;

    /**
     * @var ShippingMethodValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingMethodValidator;

    /**
     * @var string
     */
    private $violationPath;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var Constraint|\PHPUnit\Framework\MockObject\MockObject
     */
    private $constraint;

    /**
     * @var UpdateIntegrationValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->shippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $this->shippingMethodValidator = $this->createMock(ShippingMethodValidatorInterface::class);
        $this->violationPath = 'path';

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = $this->createMock(Constraint::class);

        $this->validator = new UpdateIntegrationValidator(
            $this->shippingMethodFactory,
            $this->shippingMethodValidator,
            $this->violationPath
        );
        $this->validator->initialize($this->context);
    }

    public function testValidateNotIntegrationTransport()
    {
        $this->context
            ->expects(static::never())
            ->method('buildViolation');

        $this->validator->validate(null, $this->constraint);
    }

    public function testValidateNoChannel()
    {
        $transport = $this->createMock(Transport::class);

        $this->context
            ->expects(static::never())
            ->method('buildViolation');

        $this->validator->validate($transport, $this->constraint);
    }

    public function testValidateNoErrors()
    {
        $channel = new Channel();

        $transport = $this->createMock(Transport::class);
        $transport
            ->expects(static::any())
            ->method('getChannel')
            ->willReturn($channel);

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodFactory
            ->expects(static::once())
            ->method('create')
            ->with($channel)
            ->willReturn($shippingMethod);

        $errorsCollection = $this->createMock(ShippingMethodValidatorResultErrorCollectionInterface::class);
        $errorsCollection
            ->expects(static::once())
            ->method('isEmpty')
            ->willReturn(true);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);
        $result
            ->expects(static::any())
            ->method('getErrors')
            ->willReturn($errorsCollection);

        $this->shippingMethodValidator
            ->expects(static::once())
            ->method('validate')
            ->with($shippingMethod)
            ->willReturn($result);

        $this->context
            ->expects(static::never())
            ->method('buildViolation');

        $this->validator->validate($transport, $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $channel = new Channel();

        $transport = $this->createMock(Transport::class);
        $transport
            ->expects(static::any())
            ->method('getChannel')
            ->willReturn($channel);

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodFactory
            ->expects(static::once())
            ->method('create')
            ->with($channel)
            ->willReturn($shippingMethod);

        $errorMessage = 'Error message';

        $error = $this->createMock(ShippingMethodValidatorResultErrorInterface::class);
        $error
            ->expects(static::once())
            ->method('getMessage')
            ->willReturn($errorMessage);

        $errorsCollection = new DoctrineShippingMethodValidatorResultErrorCollection();
        $errorsCollection = $errorsCollection->createCommonBuilder()->addError($error)->getCollection();

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);
        $result
            ->expects(static::any())
            ->method('getErrors')
            ->willReturn($errorsCollection);

        $this->shippingMethodValidator
            ->expects(static::once())
            ->method('validate')
            ->with($shippingMethod)
            ->willReturn($result);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder
            ->expects(static::once())
            ->method('setTranslationDomain')
            ->willReturn($violationBuilder);
        $violationBuilder
            ->expects(static::once())
            ->method('atPath')
            ->with($this->violationPath)
            ->willReturn($violationBuilder);

        $this->context->expects(static::once())
            ->method('buildViolation')
            ->with($errorMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($transport, $this->constraint);
    }
}
