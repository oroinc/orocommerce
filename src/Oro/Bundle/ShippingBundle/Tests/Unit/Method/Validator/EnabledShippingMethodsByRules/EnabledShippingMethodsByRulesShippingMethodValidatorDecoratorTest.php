<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Validator\EnabledShippingMethodsByRules;

use Oro\Bundle\ShippingBundle\Method\Exception\InvalidArgumentException;
use Oro\Bundle\ShippingBundle\Method\Provider\Label\Type\MethodTypeLabelsProviderInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Type\NonDeletable\NonDeletableMethodTypeIdentifiersProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Builder;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnabledShippingMethodsByRulesShippingMethodValidatorDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $parentShippingMethodValidator;

    /** @var Common\CommonShippingMethodValidatorResultErrorFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $errorFactory;

    /** @var NonDeletableMethodTypeIdentifiersProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $nonDeletableTypeIdentifiersProvider;

    /** @var MethodTypeLabelsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $methodTypeLabelsProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var Validator\EnabledShippingMethodsByRules\EnabledShippingMethodsByRulesShippingMethodValidatorDecorator */
    private $validator;

    protected function setUp(): void
    {
        $this->parentShippingMethodValidator = $this->createMock(ShippingMethodValidatorInterface::class);
        $this->errorFactory = $this->createMock(
            Common\CommonShippingMethodValidatorResultErrorFactoryInterface::class
        );
        $this->nonDeletableTypeIdentifiersProvider = $this->createMock(
            NonDeletableMethodTypeIdentifiersProviderInterface::class
        );
        $this->methodTypeLabelsProvider = $this->createMock(MethodTypeLabelsProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->validator =
            new Validator\EnabledShippingMethodsByRules\EnabledShippingMethodsByRulesShippingMethodValidatorDecorator(
                $this->parentShippingMethodValidator,
                $this->errorFactory,
                $this->nonDeletableTypeIdentifiersProvider,
                $this->methodTypeLabelsProvider,
                $this->translator,
                $this->logger
            );
    }

    public function testValidateNoIdentifier()
    {
        $method = $this->createMock(ShippingMethodInterface::class);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $this->parentShippingMethodValidator->expects(self::once())
            ->method('validate')
            ->with($method)
            ->willReturn($result);

        $this->nonDeletableTypeIdentifiersProvider->expects(self::once())
            ->method('getMethodTypeIdentifiers')
            ->with($method)
            ->willReturn([]);

        self::assertSame($result, $this->validator->validate($method));
    }

    public function testValidateLabelException()
    {
        $methodId = 'method_1';

        $method = $this->createMock(ShippingMethodInterface::class);
        $method->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($methodId);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $this->parentShippingMethodValidator->expects(self::once())
            ->method('validate')
            ->with($method)
            ->willReturn($result);

        $typeIdentifiers = [
            'type_1',
        ];

        $this->nonDeletableTypeIdentifiersProvider->expects(self::once())
            ->method('getMethodTypeIdentifiers')
            ->with($method)
            ->willReturn($typeIdentifiers);

        $errorMessage = 'Error message';

        $exception = new InvalidArgumentException($errorMessage);

        $this->methodTypeLabelsProvider->expects(self::once())
            ->method('getLabels')
            ->with($methodId, $typeIdentifiers)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                $errorMessage,
                [
                    'method_identifier' => $methodId,
                    'type_identifiers' => $typeIdentifiers,
                ]
            );

        self::assertSame($result, $this->validator->validate($method));
    }

    public function testValidate()
    {
        $methodId = 'method_1';

        $method = $this->createMock(ShippingMethodInterface::class);
        $method->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($methodId);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $this->parentShippingMethodValidator->expects(self::once())
            ->method('validate')
            ->with($method)
            ->willReturn($result);

        $typeIdentifiers = [
            'type_1',
            'type_2',
        ];

        $typeLabels = [
            'Label 1',
            'Label 2',
        ];

        $this->nonDeletableTypeIdentifiersProvider->expects(self::once())
            ->method('getMethodTypeIdentifiers')
            ->with($method)
            ->willReturn($typeIdentifiers);

        $this->methodTypeLabelsProvider->expects(self::once())
            ->method('getLabels')
            ->with($methodId, $typeIdentifiers)
            ->willReturn($typeLabels);

        $translatedMessage = 'validation message';

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'oro.shipping.method_type.used.error',
                ['%types%' => implode(', ', $typeLabels)]
            )
            ->willReturn($translatedMessage);

        $clonedAndBuiltErrorCollection = $this->createErrorCollection($result, $translatedMessage);

        $errorResult = $this->createErrorResult($result, $clonedAndBuiltErrorCollection);

        self::assertSame($errorResult, $this->validator->validate($method));
    }

    private function createErrorCollection(
        ShippingMethodValidatorResultInterface|\PHPUnit\Framework\MockObject\MockObject $result,
        string $translatedMessage
    ): Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface {
        $errorCollection = $this->createMock(
            Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface::class
        );

        $builder = $this->createMock(
            Builder\Common\CommonShippingMethodValidatorResultErrorCollectionBuilderInterface::class
        );

        $errorCollection->expects(self::once())
            ->method('createCommonBuilder')
            ->willReturn($builder);

        $result->expects(self::any())
            ->method('getErrors')
            ->willReturn($errorCollection);

        $error = $this->createMock(Validator\Result\Error\ShippingMethodValidatorResultErrorInterface::class);

        $this->errorFactory->expects(self::once())
            ->method('createError')
            ->with($translatedMessage)
            ->willReturn($error);

        $builder->expects(self::once())
            ->method('cloneAndBuild')
            ->with($errorCollection)
            ->willReturn($builder);

        $builder->expects(self::once())
            ->method('addError')
            ->with($error)
            ->willReturn($builder);

        $clonedAndBuiltErrorCollection = $this->createMock(
            Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface::class
        );

        $builder->expects(self::once())
            ->method('getCollection')
            ->willReturn($clonedAndBuiltErrorCollection);

        return $clonedAndBuiltErrorCollection;
    }

    private function createErrorResult(
        ShippingMethodValidatorResultInterface|\PHPUnit\Framework\MockObject\MockObject $result,
        Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $errorCollection
    ): ShippingMethodValidatorResultInterface {
        $resultFactory = $this->createMock(
            Validator\Result\Factory\Common\CommonShippingMethodValidatorResultFactoryInterface::class
        );

        $result->expects(self::once())
            ->method('createCommonFactory')
            ->willReturn($resultFactory);

        $errorResult = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $resultFactory->expects(self::once())
            ->method('createErrorResult')
            ->with($errorCollection)
            ->willReturn($errorResult);

        return $errorResult;
    }
}
