<?php

namespace Oro\Bundle\InventoryBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FormBundle\Form\DataTransformer\DataChangesetTransformer;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Form\DataTransformer\InventoryLevelGridDataTransformer as LevelTransformer;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This handler is saving the data when updating inventory levels
 */
class InventoryLevelHandler implements FormHandlerInterface
{
    protected ObjectManager $manager;
    protected RoundingServiceInterface $roundingService;
    private InventoryManager $inventoryManager;

    public function __construct(
        ObjectManager $manager,
        RoundingServiceInterface $rounding,
        InventoryManager $inventoryManager
    ) {
        $this->manager = $manager;
        $this->roundingService = $rounding;
        $this->inventoryManager = $inventoryManager;
    }

    /**
     * {@inheritDoc}
     */
    public function process($data, FormInterface $form, Request $request)
    {
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();

                if ($formData && count($formData)) {
                    $this->handleInventoryLevels($formData);
                    $this->manager->flush();
                }

                return true;
            }
        }

        return false;
    }

    protected function handleInventoryLevels($levelsData): void
    {
        foreach ($levelsData as $levelData) {
            $inventoryLevel = $this->getInventoryLevelObject($levelData);
            $hasQuantity = $inventoryLevel->getQuantity() > 0;
            $isPersisted = $inventoryLevel->getId() !== null;

            if ($hasQuantity && !$isPersisted) {
                $this->manager->persist($inventoryLevel);
            } elseif (!$hasQuantity && $isPersisted) {
                $this->manager->remove($inventoryLevel);
            }
        }
    }

    /**
     * Level data has following format
     * [
     *      'precision' => <ProductUnitPrecision>,
     *      'data' => ['levelQuantity' => <string|float|int|null>]
     * ]
     */
    protected function getInventoryLevelObject(array $levelData): InventoryLevel
    {
        /** @var ProductUnitPrecision $precision */
        $precision = $levelData[LevelTransformer::PRECISION_KEY];

        $quantity = (float)$levelData[DataChangesetTransformer::DATA_KEY]['levelQuantity'];
        $quantity = $this->roundingService->round($quantity, $precision->getPrecision());

        $level = $this->findInventoryLevel($precision);
        if (!$level) {
            $level = $this->inventoryManager->createInventoryLevel($precision);
        }
        $level->setQuantity($quantity);

        return $level;
    }

    protected function findInventoryLevel(ProductUnitPrecision $precision):? InventoryLevel
    {
        return $this->manager->getRepository(InventoryLevel::class)->findOneBy(
            ['productUnitPrecision' => $precision]
        );
    }
}
