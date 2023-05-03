<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\Grouping\QuickAddRowGrouperInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Handles quick add import form plain text.
 */
class QuickAddImportFromPlainTextHandler
{
    private QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder;
    private EventDispatcherInterface $eventDispatcher;
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
        QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder,
        EventDispatcherInterface $eventDispatcher,
        ValidatorInterface $validator,
        QuickAddRowGrouperInterface $quickAddRowGrouper,
        QuickAddRowCollectionViolationsMapper $quickAddRowCollectionViolationsMapper,
        QuickAddCollectionNormalizerInterface $quickAddCollectionNormalizer,
        PreloadingManager $preloadingManager
    ) {
        $this->quickAddRowCollectionBuilder = $quickAddRowCollectionBuilder;
        $this->eventDispatcher = $eventDispatcher;
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

            $this->validate($quickAddRowCollection);
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

    private function validate(QuickAddRowCollection $collection): void
    {
        $this->preloadingManager->preloadInEntities($collection->getProducts(), $this->preloadingConfig);
        $this->quickAddRowCollectionViolationsMapper->mapViolations(
            $collection,
            $this->validator->validate($collection)
        );
    }
}
