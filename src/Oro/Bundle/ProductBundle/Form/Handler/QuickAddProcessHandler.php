<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\Grouping\QuickAddRowGrouperInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Handles quick add form.
 */
class QuickAddProcessHandler
{
    private ComponentProcessorRegistry $componentRegistry;
    private ValidatorInterface $validator;
    private QuickAddRowGrouperInterface $quickAddRowGrouper;
    private QuickAddRowCollectionViolationsMapper $quickAddRowCollectionViolationsMapper;
    private QuickAddCollectionNormalizerInterface $quickAddCollectionNormalizer;
    private PreloadingManager $preloadingManager;

    private array $preloadingConfig = [
        'names' => [],
        'unitPrecisions' => [],
        'minimumQuantityToOrder' => [],
        'maximumQuantityToOrder' => [],
        'category' => ['minimumQuantityToOrder' => [], 'maximumQuantityToOrder' => []],
    ];

    public function __construct(
        ComponentProcessorRegistry $componentRegistry,
        ValidatorInterface $validator,
        QuickAddRowGrouperInterface $quickAddRowGrouper,
        QuickAddRowCollectionViolationsMapper $quickAddRowCollectionViolationsMapper,
        QuickAddCollectionNormalizerInterface $quickAddCollectionNormalizer,
        PreloadingManager $preloadingManager
    ) {
        $this->componentRegistry = $componentRegistry;
        $this->validator = $validator;
        $this->quickAddRowGrouper = $quickAddRowGrouper;
        $this->quickAddRowCollectionViolationsMapper = $quickAddRowCollectionViolationsMapper;
        $this->quickAddCollectionNormalizer = $quickAddCollectionNormalizer;
        $this->preloadingManager = $preloadingManager;
    }

    public function setPreloadingConfig(array $preloadingConfig): void
    {
        $this->preloadingConfig = $preloadingConfig;
    }

    public function process(FormInterface $form, Request $request): JsonResponse|array
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return [];
        }

        if ($form->isValid()) {
            $formData = $form->getData();
            /** @var QuickAddRowCollection $quickAddRowCollection */
            $quickAddRowCollection = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getData();
            $this->validate($quickAddRowCollection);

            if ($quickAddRowCollection->isValid()) {
                $this->quickAddRowGrouper->groupProducts($quickAddRowCollection);
                $this->validateAfterMerge($quickAddRowCollection, $formData[QuickAddType::COMPONENT_FIELD_NAME]);
            }

            if ($quickAddRowCollection->isValid()) {
                $entityItemsData = $quickAddRowCollection->map(static fn (QuickAddRow $quickAddRow) => [
                    ProductDataStorage::PRODUCT_SKU_KEY => $quickAddRow->getSku(),
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $quickAddRow->getQuantity(),
                    ProductDataStorage::PRODUCT_UNIT_KEY => $quickAddRow->getUnit(),
                    ProductDataStorage::PRODUCT_ORGANIZATION_KEY => $quickAddRow->getOrganization(),
                ])->toArray();

                $additionalData = $formData[QuickAddType::ADDITIONAL_FIELD_NAME] ?? null;
                $transitionName = $formData[QuickAddType::TRANSITION_FIELD_NAME] ?? null;

                $processor = $this->getProcessor($formData[QuickAddType::COMPONENT_FIELD_NAME]);
                $response = $processor->process(
                    [
                        ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $entityItemsData,
                        ProductDataStorage::ADDITIONAL_DATA_KEY => $additionalData,
                        ProductDataStorage::TRANSITION_NAME_KEY => $transitionName,
                    ],
                    $request
                );

                if ($response instanceof RedirectResponse) {
                    $responseData = [
                        'success' => true,
                        'redirectUrl' => $response->getTargetUrl(),
                    ];
                } else {
                    $flashBag = $request->getSession()->getFlashBag();
                    $responseData = [
                        'success' => !$flashBag->has('error'),
                        'messages' => $flashBag->all(),
                    ];
                }
            } else {
                $responseData = [
                    'success' => false,
                    'collection' => $this->quickAddCollectionNormalizer
                        ->normalize($quickAddRowCollection->getInvalidRows()),
                ];
            }
        } else {
            $responseData = ['success' => false];
            $formErrorIterator = $form->getErrors(true);
            if ($formErrorIterator) {
                foreach ($formErrorIterator as $formError) {
                    $responseData['messages']['error'][] = $formError->getMessage();
                }
            }
        }

        return new JsonResponse($responseData);
    }

    private function getProcessor(string $name): ?ComponentProcessorInterface
    {
        return $this->componentRegistry->getProcessorByName($name);
    }

    private function validate(QuickAddRowCollection $quickAddRowCollection): void
    {
        $this->preloadingManager->preloadInEntities($quickAddRowCollection->getProducts(), $this->preloadingConfig);
        $this->quickAddRowCollectionViolationsMapper->mapViolations(
            $quickAddRowCollection,
            $this->validator->validate($quickAddRowCollection)
        );
    }

    private function validateAfterMerge(QuickAddRowCollection $quickAddRowCollection, string $componentName): void
    {
        $violationList = $this->validator->validate($quickAddRowCollection, null, $componentName);
        $this->quickAddRowCollectionViolationsMapper->mapViolations($quickAddRowCollection, $violationList, true);
    }
}
