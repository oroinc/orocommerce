<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\Grouping\QuickAddRowGrouperInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddCollectionValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles quick add import form file form.
 */
class QuickAddImportFromFileHandler
{
    public function __construct(
        private readonly QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly QuickAddRowGrouperInterface $quickAddRowGrouper,
        private readonly QuickAddCollectionNormalizerInterface $quickAddCollectionNormalizer,
        private readonly QuickAddCollectionValidator $quickAddCollectionValidator
    ) {
    }

    public function process(FormInterface $form, Request $request): JsonResponse
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return new JsonResponse(['success' => false]);
        }

        if ($form->isValid()) {
            $file = $form->get(QuickAddImportFromFileType::FILE_FIELD_NAME)->getData();

            try {
                $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromFile($file);
                $this->quickAddRowGrouper->groupProducts($quickAddRowCollection);

                if ($quickAddRowCollection->isEmpty()) {
                    $quickAddRowCollection->addError('oro.product.frontend.quick_add.validation.empty_file');
                }
            } catch (UnsupportedTypeException $e) {
                $quickAddRowCollection = new QuickAddRowCollection();
                $quickAddRowCollection->addError('oro.product.frontend.quick_add.invalid_file_type');
            }

            $formData = $form->getData();

            $componentName = $formData[QuickAddType::COMPONENT_FIELD_NAME] ?? null;

            $this->quickAddCollectionValidator->validate($quickAddRowCollection, $componentName);

            $this->eventDispatcher->dispatch(
                new QuickAddRowsCollectionReadyEvent($quickAddRowCollection),
                QuickAddRowsCollectionReadyEvent::NAME
            );

            $responseData = [
                'success' => $quickAddRowCollection->isValid(),
                'collection' => $this->quickAddCollectionNormalizer->normalize($quickAddRowCollection),
            ];
        } else {
            $responseData = ['success' => false];
            foreach ($form->getErrors(true) as $formError) {
                $responseData['messages']['error'][] = $formError->getMessage();
            }
        }

        return new JsonResponse($responseData);
    }
}
