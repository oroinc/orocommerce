<?php

namespace Oro\Bundle\InventoryBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FormBundle\Form\DataTransformer\DataChangesetTransformer;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Form\DataTransformer\InventoryLevelGridDataTransformer as LevelTransformer;

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
                    $this->handleInventoryLevels($formData);
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
            $level = new InventoryLevel();
            $level->setProductUnitPrecision($precision);
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
        return $this->manager->getRepository('OroInventoryBundle:InventoryLevel')
            ->findOneBy(['productUnitPrecision' => $precision]);
    }
}
