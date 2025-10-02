<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\QuickAdd;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddCollectionValidator;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class QuickAddCollectionValidatorTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private PreloadingManager&MockObject $preloadingManager;
    private QuickAddRowCollectionViolationsMapper&MockObject $violationsMapper;
    private QuickAddCollectionValidator $quickAddCollectionValidator;
    private ComponentProcessorRegistry&MockObject $componentProcessorRegistry;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);
        $this->violationsMapper = $this->createMock(QuickAddRowCollectionViolationsMapper::class);
        $this->componentProcessorRegistry = $this->createMock(
            ComponentProcessorRegistry::class
        );

        $this->quickAddCollectionValidator = new QuickAddCollectionValidator(
            $this->validator,
            $this->preloadingManager,
            $this->violationsMapper,
            $this->componentProcessorRegistry,
        );
    }

    public function testThatExceptionThrownWhenNoComponentProcessorsAreAllowed(): void
    {
        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('getAllowedProcessorsNames')
            ->willReturn([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No component processors are allowed');

        $this->quickAddCollectionValidator->validate(
            new QuickAddRowCollection(),
            'component_name'
        );
    }

    public function testThatExceptionThrownWhenComponentProcessorIsNotAllowed(): void
    {
        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('getAllowedProcessorsNames')
            ->willReturn(['component1', 'component2']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Component processor "component3" is not allowed');

        $this->quickAddCollectionValidator->validate(
            new QuickAddRowCollection(),
            'component3'
        );
    }

    public function testValidationForProvidedComponentName(): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();
        $constraintViolationList = new ConstraintViolationList([]);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with(
                $quickAddRowCollection->getProducts(),
                [
                    'names' => [],
                    'unitPrecisions' => [],
                    'minimumQuantityToOrder' => [],
                    'maximumQuantityToOrder' => [],
                    'category' => ['minimumQuantityToOrder' => [], 'maximumQuantityToOrder' => []],
                ]
            );

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection, null, ['component_name'])
            ->willReturn($constraintViolationList);

        $this->violationsMapper
            ->expects(self::once())
            ->method('mapViolationsAgainstGroups')
            ->with(
                $quickAddRowCollection,
                $constraintViolationList,
                ['component_name']
            );

        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('getAllowedProcessorsNames')
            ->willReturn(['component_name']);

        $this->quickAddCollectionValidator->validate($quickAddRowCollection, 'component_name');
    }

    public function testFailedValidationForValidComponent(): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();
        $quickAddRow = new QuickAddRow(0, '1234', 5);
        $quickAddRow->addError('error');
        $quickAddRowCollection[] = $quickAddRow;

        $constraintViolationList = new ConstraintViolationList([]);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with(
                $quickAddRowCollection->getProducts(),
                [
                    'names' => [],
                    'unitPrecisions' => [],
                    'minimumQuantityToOrder' => [],
                    'maximumQuantityToOrder' => [],
                    'category' => ['minimumQuantityToOrder' => [], 'maximumQuantityToOrder' => []],
                ]
            );

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection, null, ['component_name'])
            ->willReturn($constraintViolationList);

        $this->violationsMapper
            ->expects(self::once())
            ->method('mapViolationsAgainstGroups')
            ->with(
                $quickAddRowCollection,
                $constraintViolationList,
                ['component_name']
            );

        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('getAllowedProcessorsNames')
            ->willReturn(['component_name']);

        $this->quickAddCollectionValidator->validate($quickAddRowCollection, 'component_name');
        self::assertEquals(
            'oro.product.frontend.quick_add.validation.component.component_name.error',
            $quickAddRowCollection->getErrors()[0]['message']
        );
    }

    public function testWithEmptyPreloadingParams(): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();

        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('getAllowedProcessorsNames')
            ->willReturn(['component1', 'component2', 'component3']);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with($quickAddRowCollection->getProducts(), []);

        $constraintViolationList = new ConstraintViolationList([]);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection, null, [
                'component1',
                'component2',
                'component3',
            ])
            ->willReturn($constraintViolationList);

        $this->violationsMapper
            ->expects(self::once())
            ->method('mapViolationsAgainstGroups')
            ->with(
                $quickAddRowCollection,
                $constraintViolationList,
                ['component1', 'component2', 'component3']
            );

        $this->quickAddCollectionValidator->setPreloadingConfig([]);
        $this->quickAddCollectionValidator->validate($quickAddRowCollection);
    }
}
