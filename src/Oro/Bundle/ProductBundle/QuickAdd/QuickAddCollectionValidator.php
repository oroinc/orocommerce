<?php

namespace Oro\Bundle\ProductBundle\QuickAdd;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates the collection of quick add rows.
 */
class QuickAddCollectionValidator
{
    private array $preloadingConfig = [
        'names' => [],
        'unitPrecisions' => [],
        'minimumQuantityToOrder' => [],
        'maximumQuantityToOrder' => [],
        'category' => ['minimumQuantityToOrder' => [], 'maximumQuantityToOrder' => []],
    ];

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly PreloadingManager $preloadingManager,
        private readonly QuickAddRowCollectionViolationsMapper $violationsMapper,
        private readonly ComponentProcessorRegistry $componentProcessorRegistry,
    ) {
    }

    public function setPreloadingConfig(array $preloadingConfig): void
    {
        $this->preloadingConfig = $preloadingConfig;
    }

    /**
     * Validates the QuickAdd collection using component-specific validation rules.
     *
     * @param QuickAddRowCollection $collection The collection to validate
     * @param string|null $componentName Name of the component processor from ComponentProcessorRegistry.
     *                                   Must implement ComponentProcessorValidationAwareInterface.
     *                                   If null, validation groups from all allowed processors are used.
     *                                   Example: 'oro_shopping_list_to_checkout_quick_add_processor'
     */
    public function validate(QuickAddRowCollection $collection, ?string $componentName = null): void
    {
        $validationGroups = $this->getValidationGroups($componentName);

        $this->preloadingManager->preloadInEntities($collection->getProducts(), $this->preloadingConfig);
        $this->violationsMapper->mapViolationsAgainstGroups(
            $collection,
            $this->validator->validate(
                $collection,
                null,
                $validationGroups
            ),
            $validationGroups
        );

        if (!$collection->isValid() && $componentName !== null) {
            $collection->addError(
                sprintf('oro.product.frontend.quick_add.validation.component.%s.error', $componentName)
            );
        }
    }

    private function getValidationGroups(?string $componentName = null): array
    {
        $allowedProcessorNames = $this->componentProcessorRegistry->getAllowedProcessorsNames();

        if (!$allowedProcessorNames) {
            throw new \LogicException('No component processors are allowed');
        }

        if ($componentName) {
            if (!in_array($componentName, $allowedProcessorNames)) {
                throw new \InvalidArgumentException(sprintf('Component processor "%s" is not allowed', $componentName));
            }

            return [$componentName];
        }

        return $allowedProcessorNames;
    }
}
