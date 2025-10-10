<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionIssuesNormalizer;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddCollectionValidator;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The controller for the quick order form page validation.
 */
#[AclAncestor('oro_quick_add_form')]
class QuickAddValidationController extends AbstractController
{
    #[Route(path: '/validate-rows', name: 'oro_product_frontend_quick_add_validate_rows', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid data format'
            ], 400);
        }

        $builder = $this->container->get(QuickAddRowCollectionBuilder::class);
        $collection = $builder->buildFromArray($data['items']);

        $componentName = $request->get('component');
        $validator = $this->container->get(QuickAddCollectionValidator::class);

        $this->ensureComponentProcessorIsAllowed($componentName);

        $validator->validate($collection, $componentName);

        $isValid = $collection->isValid();

        if ($isValid) {
            return new JsonResponse([
                'success' => true,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'items' => $this->container->get(QuickAddCollectionIssuesNormalizer::class)
                ->normalize($collection)
        ]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                QuickAddRowCollectionBuilder::class,
                QuickAddCollectionValidator::class,
                QuickAddCollectionIssuesNormalizer::class,
                ComponentProcessorRegistry::class,
            ]
        );
    }

    private function ensureComponentProcessorIsAllowed(?string $componentName = null): void
    {
        if (!$componentName) {
            return;
        }

        $componentProcessorRegistry = $this->container->get(ComponentProcessorRegistry::class);
        /**
         * @var ComponentProcessorInterface $componentProcessor
         */
        $componentProcessor = $componentProcessorRegistry->getProcessor($componentName);

        if (!$componentProcessor?->isAllowed()) {
            throw new \LogicException(sprintf(
                'Component processor "%s" is not allowed for validation.',
                $componentName
            ));
        }
    }
}
