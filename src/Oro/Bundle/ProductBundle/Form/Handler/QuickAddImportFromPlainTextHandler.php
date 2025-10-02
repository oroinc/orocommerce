<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\Grouping\QuickAddRowGrouperInterface;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddCollectionValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles quick add import form plain text.
 */
class QuickAddImportFromPlainTextHandler
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

        if ($form->isSubmitted() && $form->isValid()) {
            $plainText = $form->get(QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME)->getData();

            $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($plainText);
            $this->quickAddRowGrouper->groupProducts($quickAddRowCollection);

            if ($quickAddRowCollection->isEmpty()) {
                $quickAddRowCollection->addError('oro.product.at_least_one_item');
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
            $formErrorIterator = $form->getErrors(true);
            if ($formErrorIterator) {
                foreach ($formErrorIterator as $formError) {
                    $responseData['messages']['error'][] = $formError->getMessage();
                }
            }
        }

        return new JsonResponse($responseData);
    }
}
