<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddProcessHandler;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuickAddProcessHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ComponentProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $componentRegistry;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var QuickAddRowCollectionViolationsMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $quickAddRowCollectionViolationsMapper;

    /** @var QuickAddCollectionNormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $quickAddCollectionNormalizer;

    /** @var PreloadingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $preloadingManager;

    private QuickAddProcessHandler $handler;

    protected function setUp(): void
    {
        $this->componentRegistry = $this->createMock(ComponentProcessorRegistry::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->quickAddRowCollectionViolationsMapper = $this->createMock(QuickAddRowCollectionViolationsMapper::class);
        $this->quickAddCollectionNormalizer = $this->createMock(QuickAddCollectionNormalizerInterface::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $this->handler = new QuickAddProcessHandler(
            $this->componentRegistry,
            $this->validator,
            $this->quickAddRowCollectionViolationsMapper,
            $this->quickAddCollectionNormalizer
        );
        $this->handler->setPreloadingManager($this->preloadingManager);
    }

    public function testProcessWhenNotSubmitted(): void
    {
        $request = new Request();
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form
            ->expects(self::once())
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
        $form
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $form
            ->expects(self::once())
            ->method('getErrors')
            ->with(true)
            ->willReturn(new FormErrorIterator($form, $formErrors));

        self::assertEquals(new JsonResponse($expected), $this->handler->process($form, $request));
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
        $request = new Request();
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->preloadingManager
            ->expects(self::once())
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
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $quickAddRowCollection,
                null,
                new GroupSequence(['Default', 'not_request_for_quote'])
            )
            ->willReturn($violationList);

        $this->quickAddRowCollectionViolationsMapper
            ->expects(self::once())
            ->method('mapViolations')
            ->with($quickAddRowCollection, $violationList)
            ->willReturnCallback(function (QuickAddRowCollection $quickAddRowCollection) {
                $quickAddRowCollection->addError('sample error');
            });

        $normalizedCollection = [
            'errors' => [['message' => 'sample error', 'propertyPath' => '']],
            'items' => [['sample_key' => 'sample_value']],
        ];
        $this->quickAddCollectionNormalizer
            ->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($normalizedCollection);

        self::assertEquals(
            ['success' => false, 'collection' => $normalizedCollection],
            json_decode($this->handler->process($form, $request)->getContent(), true)
        );
    }

    public function testProcessWhenFormValidButItemHasErrors(): void
    {
        $request = new Request();
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->preloadingManager
            ->expects(self::once())
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
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $quickAddRowCollection,
                null,
                new GroupSequence(['Default', 'not_request_for_quote'])
            )
            ->willReturn($violationList);

        $this->quickAddRowCollectionViolationsMapper
            ->expects(self::once())
            ->method('mapViolations')
            ->with($quickAddRowCollection, $violationList)
            ->willReturnCallback(function (QuickAddRowCollection $quickAddRowCollection) {
                $quickAddRowCollection[0]->addError('sample item error');
            });

        $normalizedCollection = [
            'errors' => [],
            'items' => [['sample_key' => 'sample_value']],
        ];
        $this->quickAddCollectionNormalizer
            ->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($normalizedCollection);

        self::assertEquals(
            ['success' => false, 'collection' => $normalizedCollection],
            json_decode($this->handler->process($form, $request)->getContent(), true)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenFormValidAndResponseIsNotRedirect(): void
    {
        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->setSession($session);
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $quickAddRow->setValid(true);
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->preloadingManager
            ->expects(self::once())
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
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $quickAddRowCollection,
                null,
                new GroupSequence(['Default', 'not_request_for_quote'])
            )
            ->willReturn($violationList);

        $this->quickAddRowCollectionViolationsMapper
            ->expects(self::once())
            ->method('mapViolations')
            ->with($quickAddRowCollection, $violationList);

        $this->quickAddCollectionNormalizer
            ->expects(self::never())
            ->method('normalize');

        $componentProcessor = $this->createMock(ComponentProcessorInterface::class);
        $this->componentRegistry
            ->expects(self::once())
            ->method('getProcessorByName')
            ->with($formData[QuickAddType::COMPONENT_FIELD_NAME])
            ->willReturn($componentProcessor);

        $componentProcessor
            ->expects(self::once())
            ->method('process')
            ->with([
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => $quickAddRow->getSku(),
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => $quickAddRow->getQuantity(),
                        ProductDataStorage::PRODUCT_UNIT_KEY => $quickAddRow->getUnit(),
                    ],
                ],
                ProductDataStorage::ADDITIONAL_DATA_KEY => $formData[QuickAddType::ADDITIONAL_FIELD_NAME],
                ProductDataStorage::TRANSITION_NAME_KEY => $formData[QuickAddType::TRANSITION_FIELD_NAME],
            ], $request)
            ->willReturn(null);

        $flashBag = new FlashBag();
        $flashBag->add('error', 'sample flash error');
        $session
            ->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        self::assertEquals(
            ['success' => false, 'messages' => ['error' => ['sample flash error']]],
            json_decode($this->handler->process($form, $request)->getContent(), true)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenFormValidAndResponseIsRedirect(): void
    {
        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->setSession($session);
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $formData = [
            QuickAddType::COMPONENT_FIELD_NAME => 'sample_component',
            QuickAddType::TRANSITION_FIELD_NAME => 'sample_transition',
            QuickAddType::ADDITIONAL_FIELD_NAME => ['sample_key' => 'sample_value'],
        ];
        $form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($formData);
        $quickAddRowCollectionForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($quickAddRowCollectionForm);
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        $quickAddRow->setValid(true);
        $product = new Product();
        $quickAddRow->setProduct($product);
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollectionForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quickAddRowCollection);

        $this->preloadingManager
            ->expects(self::once())
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
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $quickAddRowCollection,
                null,
                new GroupSequence(['Default', 'not_request_for_quote'])
            )
            ->willReturn($violationList);

        $this->quickAddRowCollectionViolationsMapper
            ->expects(self::once())
            ->method('mapViolations')
            ->with($quickAddRowCollection, $violationList);

        $this->quickAddCollectionNormalizer
            ->expects(self::never())
            ->method('normalize');

        $componentProcessor = $this->createMock(ComponentProcessorInterface::class);
        $this->componentRegistry
            ->expects(self::once())
            ->method('getProcessorByName')
            ->with($formData[QuickAddType::COMPONENT_FIELD_NAME])
            ->willReturn($componentProcessor);

        $redirectResponse = new RedirectResponse('/sample/url');
        $componentProcessor
            ->expects(self::once())
            ->method('process')
            ->with([
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => $quickAddRow->getSku(),
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => $quickAddRow->getQuantity(),
                        ProductDataStorage::PRODUCT_UNIT_KEY => $quickAddRow->getUnit(),
                    ],
                ],
                ProductDataStorage::ADDITIONAL_DATA_KEY => $formData[QuickAddType::ADDITIONAL_FIELD_NAME],
                ProductDataStorage::TRANSITION_NAME_KEY => $formData[QuickAddType::TRANSITION_FIELD_NAME],
            ], $request)
            ->willReturn($redirectResponse);

        self::assertEquals(
            ['success' => true, 'redirectUrl' => $redirectResponse->getTargetUrl()],
            json_decode($this->handler->process($form, $request)->getContent(), true)
        );
    }
}
