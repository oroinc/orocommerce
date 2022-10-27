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
    /**
     * @var ShippingMethodValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentShippingMethodValidator;

    /**
     * @var Common\CommonShippingMethodValidatorResultErrorFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $errorFactory;

    /**
     * @var NonDeletableMethodTypeIdentifiersProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $nonDeletableTypeIdentifiersProvider;

    /**
     * @var MethodTypeLabelsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $methodTypeLabelsProvider;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var Validator\EnabledShippingMethodsByRules\EnabledShippingMethodsByRulesShippingMethodValidatorDecorator
     */
    private $validator;

    /**
     * {@inheritDoc}
     */
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
        $method = $this->getShippingMethodMock();

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $this->parentShippingMethodValidator->expects(static::once())
            ->method('validate')
            ->with($method)
            ->willReturn($result);

        $this->nonDeletableTypeIdentifiersProvider->expects(static::once())
            ->method('getMethodTypeIdentifiers')
            ->with($method)
            ->willReturn([]);

        static::assertSame($result, $this->validator->validate($method));
    }

    public function testValidateLabelException()
    {
        $methodId = 'method_1';

        $method = $this->getShippingMethodMock();
        $method->expects(static::once())
            ->method('getIdentifier')
            ->willReturn($methodId);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $this->parentShippingMethodValidator->expects(static::once())
            ->method('validate')
            ->with($method)
            ->willReturn($result);

        $typeIdentifiers = [
            'type_1',
        ];

        $this->nonDeletableTypeIdentifiersProvider->expects(static::once())
            ->method('getMethodTypeIdentifiers')
            ->with($method)
            ->willReturn($typeIdentifiers);

        $errorMessage = 'Error message';

        $exception = new InvalidArgumentException($errorMessage);

        $this->methodTypeLabelsProvider->expects(static::once())
            ->method('getLabels')
            ->with($methodId, $typeIdentifiers)
            ->willThrowException($exception);

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                $errorMessage,
                [
                    'method_identifier' => $methodId,
                    'type_identifiers' => $typeIdentifiers,
                ]
            );

        static::assertSame($result, $this->validator->validate($method));
    }

    public function testValidate()
    {
        $methodId = 'method_1';

        $method = $this->getShippingMethodMock();
        $method->expects(static::once())
            ->method('getIdentifier')
            ->willReturn($methodId);

        $result = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $this->parentShippingMethodValidator->expects(static::once())
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

        $this->nonDeletableTypeIdentifiersProvider->expects(static::once())
            ->method('getMethodTypeIdentifiers')
            ->with($method)
            ->willReturn($typeIdentifiers);

        $this->methodTypeLabelsProvider->expects(static::once())
            ->method('getLabels')
            ->with($methodId, $typeIdentifiers)
            ->willReturn($typeLabels);

        $translatedMessage = 'validation message';

        $this->translator->expects(static::once())
            ->method('trans')
            ->with(
                'oro.shipping.method_type.used.error',
                ['%types%' => implode(', ', $typeLabels)]
            )
            ->willReturn($translatedMessage);

        $clonedAndBuiltErrorCollection = $this->createErrorCollectionMock($result, $translatedMessage);

        $errorResult = $this->createErrorResultMock($result, $clonedAndBuiltErrorCollection);

        static::assertSame($errorResult, $this->validator->validate($method));
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $result
     * @param string                                         $translatedMessage
     *
     * @return Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createErrorCollectionMock(\PHPUnit\Framework\MockObject\MockObject $result, $translatedMessage)
    {
        $errorCollection = $this->createMock(
            Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface::class
        );

        $builder = $this->createMock(
            Builder\Common\CommonShippingMethodValidatorResultErrorCollectionBuilderInterface::class
        );

        $errorCollection->expects(static::once())
            ->method('createCommonBuilder')
            ->willReturn($builder);

        $result->expects(static::any())
            ->method('getErrors')
            ->willReturn($errorCollection);

        $error = $this->createMock(Validator\Result\Error\ShippingMethodValidatorResultErrorInterface::class);

        $this->errorFactory->expects(static::once())
            ->method('createError')
            ->with($translatedMessage)
            ->willReturn($error);

        $builder->expects(static::once())
            ->method('cloneAndBuild')
            ->with($errorCollection)
            ->willReturn($builder);

        $builder->expects(static::once())
            ->method('addError')
            ->with($error)
            ->willReturn($builder);

        $clonedAndBuiltErrorCollection = $this->createMock(
            Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface::class
        );

        $builder->expects(static::once())
            ->method('getCollection')
            ->willReturn($clonedAndBuiltErrorCollection);

        return $clonedAndBuiltErrorCollection;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject                                                $result
     * @param Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $errorCollection
     *
     * @return ShippingMethodValidatorResultInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createErrorResultMock(
        \PHPUnit\Framework\MockObject\MockObject $result,
        Validator\Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $errorCollection
    ) {
        $resultFactory = $this->createMock(
            Validator\Result\Factory\Common\CommonShippingMethodValidatorResultFactoryInterface::class
        );

        $result->expects(static::once())
            ->method('createCommonFactory')
            ->willReturn($resultFactory);

        $errorResult = $this->createMock(ShippingMethodValidatorResultInterface::class);

        $resultFactory->expects(static::once())
            ->method('createErrorResult')
            ->with($errorCollection)
            ->willReturn($errorResult);

        return $errorResult;
    }

    /**
     * @param string   $methodIdentifier
     * @param string[] $methodTypeIdentifiers
     *
     * @return string[]
     */
    private function getShippingMethodTypesLabels($methodIdentifier, array $methodTypeIdentifiers)
    {
        try {
            return $this->methodTypeLabelsProvider->getLabels($methodIdentifier, $methodTypeIdentifiers);
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage(), [
                'method_identifier' => $methodIdentifier,
                'type_identifiers' => $methodTypeIdentifiers,
            ]);

            return [];
        }
    }

    /**
     * @return ShippingMethodInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShippingMethodMock()
    {
        return $this->createMock(ShippingMethodInterface::class);
    }
}
