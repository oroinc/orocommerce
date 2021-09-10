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
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

// @codingStandardsIgnoreEnd

class UpdateIntegrationValidatorTest extends ConstraintValidatorTestCase
{
    private IntegrationShippingMethodFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $shippingMethodFactory;

    private ShippingMethodValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $shippingMethodValidator;

    private string $violationPath;

    protected function setUp(): void
    {
        $this->shippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $this->shippingMethodValidator = $this->createMock(ShippingMethodValidatorInterface::class);
        $this->violationPath = 'path';

        parent::setUp();
    }

    protected function createValidator()
    {
        return new UpdateIntegrationValidator(
            $this->shippingMethodFactory,
            $this->shippingMethodValidator,
            $this->violationPath
        );
    }

    public function testValidateNotIntegrationTransport(): void
    {
        $this->validator->validate(null, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateNoChannel(): void
    {
        $transport = $this->createMock(Transport::class);

        $this->validator->validate($transport, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateNoErrors(): void
    {
        $channel = new Channel();

        $transport = $this->createMock(Transport::class);
        $transport
            ->expects(self::any())
            ->method('getChannel')
            ->willReturn($channel);

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodFactory
            ->expects(self::once())
            ->method('create')
            ->with($channel)
            ->willReturn($shippingMethod);

        $errorsCollection = $this->createMock(ShippingMethodValidatorResultErrorCollectionInterface::class);
        $errorsCollection
            ->expects(self::once())
            ->method('isEmpty')
            ->willReturn(true);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);
        $result
            ->expects(self::any())
            ->method('getErrors')
            ->willReturn($errorsCollection);

        $this->shippingMethodValidator
            ->expects(self::once())
            ->method('validate')
            ->with($shippingMethod)
            ->willReturn($result);

        $this->validator->validate($transport, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithErrors(): void
    {
        $channel = new Channel();

        $transport = $this->createMock(Transport::class);
        $transport
            ->expects(self::any())
            ->method('getChannel')
            ->willReturn($channel);

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodFactory
            ->expects(self::once())
            ->method('create')
            ->with($channel)
            ->willReturn($shippingMethod);

        $errorMessage = 'Error message';

        $error = $this->createMock(ShippingMethodValidatorResultErrorInterface::class);
        $error
            ->expects(self::once())
            ->method('getMessage')
            ->willReturn($errorMessage);

        $errorsCollection = new DoctrineShippingMethodValidatorResultErrorCollection();
        $errorsCollection = $errorsCollection->createCommonBuilder()->addError($error)->getCollection();

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);
        $result
            ->expects(self::any())
            ->method('getErrors')
            ->willReturn($errorsCollection);

        $this->shippingMethodValidator
            ->expects(self::once())
            ->method('validate')
            ->with($shippingMethod)
            ->willReturn($result);

        $this->validator->validate($transport, $this->constraint);

        $this->buildViolation($errorMessage)
            ->atPath($this->propertyPath . '.' . $this->violationPath)
            ->assertRaised();
    }
}
