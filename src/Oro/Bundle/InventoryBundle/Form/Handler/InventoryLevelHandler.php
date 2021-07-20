<?php

namespace Oro\Bundle\InventoryBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FormBundle\Form\DataTransformer\DataChangesetTransformer;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Form\DataTransformer\InventoryLevelGridDataTransformer as LevelTransformer;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This handler is saving the data when updating inventory levels
 */
class InventoryLevelHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RoundingServiceInterface
     */
    protected $roundingService;

    /**
     * @var InventoryManager
     */
    private $inventoryManager;

    public function __construct(
        FormInterface $form,
        ObjectManager $manager,
        Request $request,
        RoundingServiceInterface $rounding,
        InventoryManager $inventoryManager
    ) {
        $this->form = $form;
        $this->manager = $manager;
        $this->request = $request;
        $this->roundingService = $rounding;
        $this->inventoryManager = $inventoryManager;
    }

    /**
     * @return bool
     */
    public function process()
    {
        if ($this->request->isMethod('POST')) {
            $this->form->handleRequest($this->request);

            if ($this->form->isSubmitted() && $this->form->isValid()) {
                $formData = $this->form->getData();

                if ($formData && count($formData)) {
                    $this->handleInventoryLevels($formData);
                    $this->manager->flush();
                }

                return true;
            }
        }

        return false;
    }

    protected function handleInventoryLevels($levelsData)
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
     *
     * @param array $levelData
     * @return InventoryLevel
     */
    protected function getInventoryLevelObject(array $levelData)
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

    /**
     * @param ProductUnitPrecision $precision
     * @return InventoryLevel|null
     */
    protected function findInventoryLevel(ProductUnitPrecision $precision)
    {
        return $this->manager->getRepository(InventoryLevel::class)->findOneBy(
            ['productUnitPrecision' => $precision]
        );
    }
}
