<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddProcessHandler;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\Grouping\QuickAddRowGrouperInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddCollectionValidator;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class QuickAddProcessHandlerTest extends TestCase
{
    private ComponentProcessorRegistry&MockObject $processorRegistry;

    private ValidatorInterface&MockObject $validator;

    private QuickAddRowCollectionViolationsMapper&MockObject $quickAddRowCollectionViolationsMapper;

    private QuickAddCollectionNormalizerInterface&MockObject $quickAddCollectionNormalizer;

    private PreloadingManager&MockObject $preloadingManager;

    private QuickAddRowGrouperInterface&MockObject $quickAddRowGrouper;

    private QuickAddCollectionValidator&MockObject $quickAddCollectionValidator;

    private QuickAddProcessHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ComponentProcessorRegistry::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->quickAddRowGrouper = $this->createMock(QuickAddRowGrouperInterface::class);
        $this->quickAddRowCollectionViolationsMapper = $this->createMock(QuickAddRowCollectionViolationsMapper::class);
        $this->quickAddCollectionNormalizer = $this->createMock(QuickAddCollectionNormalizerInterface::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);
        $this->quickAddCollectionValidator = $this->createMock(QuickAddCollectionValidator::class);

        $this->handler = new QuickAddProcessHandler(
            $this->processorRegistry,
            $this->validator,
            $this->quickAddRowGrouper,
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

        self::assertSame([], $this->handler->process($form, $request));
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

        $response = $this->handler->process($form, $request);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(json_encode($expected, JSON_THROW_ON_ERROR), $response->getContent());
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

    public function testProcessWhenFormValidButCollectionHasErrors(): void
    {
        $this->handler->setQuickAddCollectionValidator($this->quickAddCollectionValidator);

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

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRow->addError('sample error');
        $quickAddRowCollectionForm->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->quickAddCollectionValidator->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection);

        $this->quickAddRowGrouper->expects(self::never())
            ->method(self::anything());

        $normalizedCollection = [
            'errors' => [['message' => 'sample error', 'propertyPath' => '']],
            'items' => [['sample_key' => 'sample_value']],
        ];
        $this->quickAddCollectionNormalizer->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($normalizedCollection);

        $response = $this->handler->process($form, $request);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(
            json_encode(['success' => false, 'collection' => $normalizedCollection], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    public function testProcessWhenFormValidButItemHasErrors(): void
    {
        $this->handler->setQuickAddCollectionValidator($this->quickAddCollectionValidator);

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

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);
        $quickAddRowCollection[0]->addError('sample item error');

        $this->quickAddRowGrouper->expects(self::never())
            ->method(self::anything());

        $this->quickAddCollectionValidator->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection);


        $normalizedCollection = [
            'errors' => [],
            'items' => [['sample_key' => 'sample_value']],
        ];
        $this->quickAddCollectionNormalizer->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($normalizedCollection);

        $response = $this->handler->process($form, $request);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(
            json_encode(['success' => false, 'collection' => $normalizedCollection], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenFormValidButItemHasErrorsAfterMerge(): void
    {
        $this->handler->setQuickAddCollectionValidator($this->quickAddCollectionValidator);

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

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->quickAddRowGrouper->expects(self::once())
            ->method('groupProducts')
            ->with($quickAddRowCollection);

        $violationListBeforeProcess = new ConstraintViolationList();
        $this->quickAddCollectionValidator->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection, $formData[QuickAddType::COMPONENT_FIELD_NAME]);

        $violationList = new ConstraintViolationList();
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $quickAddRowCollection,
                null,
                $formData[QuickAddType::COMPONENT_FIELD_NAME]
            )
            ->willReturn($violationList, $violationListBeforeProcess);

        $this->quickAddRowCollectionViolationsMapper->expects(self::once())
            ->method('mapViolations')
            ->with(
                self::callback(static function (QuickAddRowCollection $quickAddRowCollection) {
                    $quickAddRowCollection[0]->addError('sample item error');

                    return true;
                }),
                $violationListBeforeProcess,
                true,
            );

        $normalizedCollection = [
            'errors' => [],
            'items' => [['sample_key' => 'sample_value']],
        ];
        $this->quickAddCollectionNormalizer->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($normalizedCollection);

        $response = $this->handler->process($form, $request);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(
            json_encode(['success' => false, 'collection' => $normalizedCollection], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenFormValidAndResponseIsNotRedirect(): void
    {
        $this->handler->setQuickAddCollectionValidator($this->quickAddCollectionValidator);

        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->setSession($session);
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

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item', 'Org');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->quickAddRowGrouper->expects(self::once())
            ->method('groupProducts')
            ->with($quickAddRowCollection);

        $violationList = new ConstraintViolationList();
        $violationListBeforeProcess = new ConstraintViolationList();
        $this->quickAddCollectionValidator->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection, $formData[QuickAddType::COMPONENT_FIELD_NAME]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $quickAddRowCollection,
                null,
                $formData[QuickAddType::COMPONENT_FIELD_NAME]
            )
            ->willReturn($violationList, $violationListBeforeProcess);

        $this->quickAddRowCollectionViolationsMapper->expects(self::once())
            ->method('mapViolations')
            ->with(
                $quickAddRowCollection,
                $violationListBeforeProcess,
                true
            );

        $this->quickAddCollectionNormalizer->expects(self::never())
            ->method('normalize');

        $componentProcessor = $this->createMock(ComponentProcessorInterface::class);
        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($formData[QuickAddType::COMPONENT_FIELD_NAME])
            ->willReturn($componentProcessor);

        $componentProcessor->expects(self::once())
            ->method('process')
            ->with([
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => $quickAddRow->getSku(),
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => $quickAddRow->getQuantity(),
                        ProductDataStorage::PRODUCT_UNIT_KEY => $quickAddRow->getUnit(),
                        ProductDataStorage::PRODUCT_ORGANIZATION_KEY => $quickAddRow->getOrganization(),
                    ],
                ],
                ProductDataStorage::ADDITIONAL_DATA_KEY => $formData[QuickAddType::ADDITIONAL_FIELD_NAME],
                ProductDataStorage::TRANSITION_NAME_KEY => $formData[QuickAddType::TRANSITION_FIELD_NAME],
            ], $request)
            ->willReturn(null);

        $flashBag = new FlashBag();
        $flashBag->add('error', 'sample flash error');
        $session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $response = $this->handler->process($form, $request);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(
            json_encode(['success' => false, 'messages' => ['error' => ['sample flash error']]], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenFormValidAndResponseIsRedirect(): void
    {
        $this->handler->setQuickAddCollectionValidator($this->quickAddCollectionValidator);

        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->setSession($session);
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

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item', 'Org');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->quickAddRowGrouper->expects(self::once())
            ->method('groupProducts')
            ->with($quickAddRowCollection);

        $violationList = new ConstraintViolationList();
        $violationListBeforeProcess = new ConstraintViolationList();
        $this->quickAddCollectionValidator->expects(self::once())
            ->method('validate')
            ->with($quickAddRowCollection, $formData[QuickAddType::COMPONENT_FIELD_NAME]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $quickAddRowCollection,
                null,
                $formData[QuickAddType::COMPONENT_FIELD_NAME]
            )
            ->willReturn($violationList, $violationListBeforeProcess);

        $this->quickAddRowCollectionViolationsMapper->expects(self::once())
            ->method('mapViolations')
            ->with(
                $quickAddRowCollection,
                $violationListBeforeProcess,
                true
            );

        $this->quickAddCollectionNormalizer->expects(self::never())
            ->method('normalize');

        $componentProcessor = $this->createMock(ComponentProcessorInterface::class);
        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($formData[QuickAddType::COMPONENT_FIELD_NAME])
            ->willReturn($componentProcessor);

        $redirectResponse = new RedirectResponse('/sample/url');
        $componentProcessor->expects(self::once())
            ->method('process')
            ->with([
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => $quickAddRow->getSku(),
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => $quickAddRow->getQuantity(),
                        ProductDataStorage::PRODUCT_UNIT_KEY => $quickAddRow->getUnit(),
                        ProductDataStorage::PRODUCT_ORGANIZATION_KEY => $quickAddRow->getOrganization(),
                    ],
                ],
                ProductDataStorage::ADDITIONAL_DATA_KEY => $formData[QuickAddType::ADDITIONAL_FIELD_NAME],
                ProductDataStorage::TRANSITION_NAME_KEY => $formData[QuickAddType::TRANSITION_FIELD_NAME],
            ], $request)
            ->willReturn($redirectResponse);

        $response = $this->handler->process($form, $request);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(
            json_encode(['success' => true, 'redirectUrl' => $redirectResponse->getTargetUrl()], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenFormValidAndResponseIsRedirectAndQuickAddCollectionValidatorNotSet(): void
    {
        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->setSession($session);
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

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item', 'Org');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->quickAddRowGrouper->expects(self::once())
            ->method('groupProducts')
            ->with($quickAddRowCollection);

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
        $violationListBeforeProcess = new ConstraintViolationList();
        $this->validator->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$quickAddRowCollection],
                [$quickAddRowCollection, null, $formData[QuickAddType::COMPONENT_FIELD_NAME]]
            )
            ->willReturn($violationList, $violationListBeforeProcess);

        $this->quickAddRowCollectionViolationsMapper->expects(self::exactly(2))
            ->method('mapViolations')
            ->withConsecutive(
                [$quickAddRowCollection, $violationList],
                [$quickAddRowCollection, $violationListBeforeProcess, true]
            );

        $this->quickAddCollectionNormalizer->expects(self::never())
            ->method('normalize');

        $componentProcessor = $this->createMock(ComponentProcessorInterface::class);
        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($formData[QuickAddType::COMPONENT_FIELD_NAME])
            ->willReturn($componentProcessor);

        $redirectResponse = new RedirectResponse('/sample/url');
        $componentProcessor->expects(self::once())
            ->method('process')
            ->with([
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => $quickAddRow->getSku(),
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => $quickAddRow->getQuantity(),
                        ProductDataStorage::PRODUCT_UNIT_KEY => $quickAddRow->getUnit(),
                        ProductDataStorage::PRODUCT_ORGANIZATION_KEY => $quickAddRow->getOrganization(),
                    ],
                ],
                ProductDataStorage::ADDITIONAL_DATA_KEY => $formData[QuickAddType::ADDITIONAL_FIELD_NAME],
                ProductDataStorage::TRANSITION_NAME_KEY => $formData[QuickAddType::TRANSITION_FIELD_NAME],
            ], $request)
            ->willReturn($redirectResponse);

        $response = $this->handler->process($form, $request);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(
            json_encode(['success' => true, 'redirectUrl' => $redirectResponse->getTargetUrl()], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }
}
