<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Handles quick add import form plain text.
 */
class QuickAddImportFromPlainTextHandler
{
    private QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder;

    private EventDispatcherInterface $eventDispatcher;

    private ValidatorInterface $validator;

    private QuickAddRowCollectionViolationsMapper $quickAddRowCollectionViolationsMapper;

    private QuickAddCollectionNormalizerInterface $quickAddCollectionNormalizer;

    private ?PreloadingManager $preloadingManager = null;

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
        QuickAddRowCollectionViolationsMapper $quickAddRowCollectionViolationsMapper,
        QuickAddCollectionNormalizerInterface $quickAddCollectionNormalizer
    ) {
        $this->quickAddRowCollectionBuilder = $quickAddRowCollectionBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
        $this->quickAddRowCollectionViolationsMapper = $quickAddRowCollectionViolationsMapper;
        $this->quickAddCollectionNormalizer = $quickAddCollectionNormalizer;
    }

    public function setPreloadingManager(?PreloadingManager $preloadingManager): void
    {
        $this->preloadingManager = $preloadingManager;
    }

    public function setPreloadingConfig(array $preloadingConfig): void
    {
        $this->preloadingConfig = $preloadingConfig;
    }

    /**
     * @param FormInterface $form
     * @param Request $request
     * @return Response
     */
    public function process(FormInterface $form, Request $request)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainText = $form->get(QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME)->getData();

            $quickAddRowCollection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($plainText);
            if (!$quickAddRowCollection->count()) {
                $quickAddRowCollection->addError('oro.product.at_least_one_item');
            }

            $this->validateCollection($quickAddRowCollection);
            $this->eventDispatcher->dispatch(
                new QuickAddRowsCollectionReadyEvent($quickAddRowCollection),
                QuickAddRowsCollectionReadyEvent::NAME
            );

            $responseData = [
                'success' => !$quickAddRowCollection->hasErrors(),
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

    private function validateCollection(QuickAddRowCollection $collection): void
    {
        if ($this->preloadingManager) {
            $this->preloadingManager
                ->preloadInEntities(array_values($collection->getProducts()), $this->preloadingConfig);
        }

        $violationList = $this->validator->validate($collection, null, new GroupSequence(['Default']));
        $this->quickAddRowCollectionViolationsMapper->mapViolations($collection, $violationList);
    }
}
