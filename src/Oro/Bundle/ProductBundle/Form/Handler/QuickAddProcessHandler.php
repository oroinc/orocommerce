<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\Grouping\QuickAddRowGrouperInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddCollectionValidator;
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
    public function __construct(
        private readonly ComponentProcessorRegistry $processorRegistry,
        private readonly ValidatorInterface $validator,
        private readonly QuickAddRowGrouperInterface $quickAddRowGrouper,
        private readonly QuickAddRowCollectionViolationsMapper $quickAddRowCollectionViolationsMapper,
        private readonly QuickAddCollectionNormalizerInterface $quickAddCollectionNormalizer,
        private readonly QuickAddCollectionValidator $quickAddCollectionValidator
    ) {
    }

    public function process(FormInterface $form, Request $request): JsonResponse|array
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return [];
        }

        if (!$form->isValid()) {
            $responseData = ['success' => false];
            $formErrorIterator = $form->getErrors(true);
            if ($formErrorIterator) {
                foreach ($formErrorIterator as $formError) {
                    $responseData['messages']['error'][] = $formError->getMessage();
                }
            }

            return new JsonResponse($responseData);
        }

        $formData = $form->getData();
        /** @var QuickAddRowCollection $quickAddRowCollection */
        $quickAddRowCollection = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getData();
        $componentName = $formData[QuickAddType::COMPONENT_FIELD_NAME] ?? null;

        $this->quickAddCollectionValidator->validate($quickAddRowCollection, $componentName);

        if ($quickAddRowCollection->isValid()) {
            $this->quickAddRowGrouper->groupProducts($quickAddRowCollection);
            $this->validateAfterMerge($quickAddRowCollection, $componentName);
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

            $processor = $this->processorRegistry->getProcessor($formData[QuickAddType::COMPONENT_FIELD_NAME]);
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

        return new JsonResponse($responseData);
    }

    private function validateAfterMerge(QuickAddRowCollection $quickAddRowCollection, string $componentName): void
    {
        $violationList = $this->validator->validate($quickAddRowCollection, null, $componentName);
        $this->quickAddRowCollectionViolationsMapper->mapViolations(
            $quickAddRowCollection,
            $violationList,
            true
        );
    }
}
