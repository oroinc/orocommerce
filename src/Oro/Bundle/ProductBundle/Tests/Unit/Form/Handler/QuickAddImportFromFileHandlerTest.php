<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddImportFromFileHandler;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\Grouping\QuickAddRowGrouper;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddCollectionValidator;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class QuickAddImportFromFileHandlerTest extends TestCase
{
    private QuickAddRowCollectionBuilder&MockObject $quickAddRowCollectionBuilder;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private ValidatorInterface&MockObject $validator;

    private QuickAddRowCollectionViolationsMapper&MockObject $quickAddRowCollectionViolationsMapper;

    private QuickAddCollectionNormalizerInterface&MockObject $quickAddCollectionNormalizer;

    private PreloadingManager&MockObject $preloadingManager;

    private QuickAddCollectionValidator&MockObject $quickAddCollectionValidator;

    private QuickAddImportFromFileHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->quickAddRowCollectionBuilder = $this->createMock(QuickAddRowCollectionBuilder::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->quickAddRowCollectionViolationsMapper = $this->createMock(QuickAddRowCollectionViolationsMapper::class);
        $this->quickAddCollectionNormalizer = $this->createMock(QuickAddCollectionNormalizerInterface::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);
        $this->quickAddCollectionValidator = $this->createMock(QuickAddCollectionValidator::class);

        $this->handler = new QuickAddImportFromFileHandler(
            $this->quickAddRowCollectionBuilder,
            $this->eventDispatcher,
            $this->validator,
            new QuickAddRowGrouper(),
            $this->quickAddRowCollectionViolationsMapper,
            $this->quickAddCollectionNormalizer,
            $this->preloadingManager
        );
    }

    public function testProcessWhenNotSubmitted(): void
    {
        $request = new Request();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        self::assertEquals(
            ['success' => false],
            json_decode($this->handler->process($form, $request)->getContent(), true)
        );
    }

    /**
     * @dataProvider processWhenNotValidDataProvider
     */
    public function testProcessWhenNotValid(array $formErrors, array $expected): void
    {
        $request = new Request();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);
        $form->expects(self::once())
            ->method('getErrors')
            ->with(true)
            ->willReturn(new FormErrorIterator($form, $formErrors));

        self::assertEquals($expected, json_decode($this->handler->process($form, $request)->getContent(), true));
    }

    public function processWhenNotValidDataProvider(): array
    {
        return [
            'no errors' => ['formErrors' => [], 'expected' => ['success' => false]],
            'has errors' => [
                'formErrors' => [new FormError('sample error')],
                'expected' => ['success' => false, 'messages' => ['error' => ['sample error']]],
            ],
        ];
    }

    /**
     * @dataProvider componentDataProvider
     */
    public function testProcessWhenValid(?string $component): void
    {
        $request = new Request();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $fileForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddImportFromFileType::FILE_FIELD_NAME)
            ->willReturn($fileForm);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn([
                'component' => $component,
            ]);
        $file = $this->createMock(UploadedFile::class);
        $fileForm->expects(self::once())
            ->method('getData')
            ->willReturn($file);

        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromFile')
            ->with($file)
            ->willReturn($quickAddRowCollection);

        $this->quickAddCollectionValidator->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection, $component);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new QuickAddRowsCollectionReadyEvent($quickAddRowCollection),
                QuickAddRowsCollectionReadyEvent::NAME
            );

        $normalizedCollection = ['errors' => [], 'items' => [['sample_key' => 'sample_value']]];
        $this->quickAddCollectionNormalizer->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($normalizedCollection);

        $this->handler->setQuickAddCollectionValidator($this->quickAddCollectionValidator);

        self::assertEquals(
            ['success' => true, 'collection' => $normalizedCollection],
            json_decode($this->handler->process($form, $request)->getContent(), true)
        );
    }

    public function testProcessWhenValidAndQuickAddCollectionValidatorNotSet(): void
    {
        $request = new Request();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $fileForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddImportFromFileType::FILE_FIELD_NAME)
            ->willReturn($fileForm);
        $file = $this->createMock(UploadedFile::class);
        $fileForm->expects(self::once())
            ->method('getData')
            ->willReturn($file);

        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $this->quickAddRowCollectionBuilder->expects(self::once())
            ->method('buildFromFile')
            ->with($file)
            ->willReturn($quickAddRowCollection);

        $this->preloadingManager->expects(self::once())
            ->method('preloadInEntities')
            ->with(
                [$product],
                [
                    'names' => [],
                    'unitPrecisions' => [],
                    'minimumQuantityToOrder' => [],
                    'maximumQuantityToOrder' => [],
                    'category' => ['minimumQuantityToOrder' => [], 'maximumQuantityToOrder' => []],
                ]
            );

        $violationList = new ConstraintViolationList();
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection)
            ->willReturn($violationList);

        $this->quickAddRowCollectionViolationsMapper->expects(self::once())
            ->method('mapViolations')
            ->with($quickAddRowCollection, $violationList);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new QuickAddRowsCollectionReadyEvent($quickAddRowCollection),
                QuickAddRowsCollectionReadyEvent::NAME
            );

        $normalizedCollection = ['errors' => [], 'items' => [['sample_key' => 'sample_value']]];
        $this->quickAddCollectionNormalizer->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($normalizedCollection);

        self::assertEquals(
            ['success' => true, 'collection' => $normalizedCollection],
            json_decode($this->handler->process($form, $request)->getContent(), true)
        );
    }

    public static function componentDataProvider(): array
    {
        return [
            'no component' => [null],
            'with component' => ['test']
        ];
    }
}
