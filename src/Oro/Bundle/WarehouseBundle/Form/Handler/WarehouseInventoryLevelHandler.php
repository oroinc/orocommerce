<?php

namespace Oro\Bundle\WarehouseBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FormBundle\Form\DataTransformer\DataChangesetTransformer;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use Oro\Bundle\WarehouseBundle\Form\DataTransformer\WarehouseInventoryLevelGridDataTransformer as LevelTransformer;

class WarehouseInventoryLevelHandler
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
     * @param FormInterface $form
     * @param ObjectManager $manager
     * @param Request $request
     * @param RoundingServiceInterface $rounding
     */
    public function __construct(
        FormInterface $form,
        ObjectManager $manager,
        Request $request,
        RoundingServiceInterface $rounding
    ) {
        $this->form = $form;
        $this->manager = $manager;
        $this->request = $request;
        $this->roundingService = $rounding;
    }

    /**
     * @return bool
     */
    public function process()
    {
        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $formData = $this->form->getData();

                if ($formData && count($formData)) {
                    $this->handleWarehouseInventoryLevels($formData);
                    $this->manager->flush();
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @param $levelsData array|Collection
     */
    protected function handleWarehouseInventoryLevels($levelsData)
    {
        foreach ($levelsData as $levelData) {
            $warehouseInventoryLevel = $this->getWarehouseInventoryLevelObject($levelData);
            $hasQuantity = $warehouseInventoryLevel->getQuantity() > 0;
            $isPersisted = $warehouseInventoryLevel->getId() !== null;

            if ($hasQuantity && !$isPersisted) {
                $this->manager->persist($warehouseInventoryLevel);
            } elseif (!$hasQuantity && $isPersisted) {
                $this->manager->remove($warehouseInventoryLevel);
            }
        }
    }

    /**
     * Level data has following format
     * [
     *      'warehouse' => <Warehouse>,
     *      'precision' => <ProductUnitPrecision>,
     *      'data' => ['levelQuantity' => <string|float|int|null>]
     * ]
     *
     * @param array $levelData
     * @return WarehouseInventoryLevel
     */
    protected function getWarehouseInventoryLevelObject(array $levelData)
    {
        /** @var Warehouse $warehouse */
        $warehouse = $levelData[LevelTransformer::WAREHOUSE_KEY];
        /** @var ProductUnitPrecision $precision */
        $precision = $levelData[LevelTransformer::PRECISION_KEY];

        $quantity = (float)$levelData[DataChangesetTransformer::DATA_KEY]['levelQuantity'];
        $quantity = $this->roundingService->round($quantity, $precision->getPrecision());

        $level = $this->findWarehouseInventoryLevel($warehouse, $precision);
        if (!$level) {
            $level = new WarehouseInventoryLevel();
            $level->setWarehouse($warehouse)
                ->setProductUnitPrecision($precision);
        }
        $level->setQuantity($quantity);

        return $level;
    }

    /**
     * @param Warehouse $warehouse
     * @param ProductUnitPrecision $precision
     * @return WarehouseInventoryLevel|null
     */
    protected function findWarehouseInventoryLevel(Warehouse $warehouse, ProductUnitPrecision $precision)
    {
        return $this->manager->getRepository('OroWarehouseBundle:WarehouseInventoryLevel')
            ->findOneBy(['warehouse' => $warehouse, 'productUnitPrecision' => $precision]);
    }
}
