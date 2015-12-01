<?php

namespace OroB2B\Bundle\WarehouseBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelGridHandler
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
     * @var RoundingService
     */
    protected $rounding;

    /**
     * WarehouseInventoryLevelGridHandler constructor.
     *
     * @param FormInterface     $form
     * @param ObjectManager     $manager
     * @param Request           $request
     * @param RoundingService   $rounding
     */
    public function __construct(
        FormInterface $form,
        ObjectManager $manager,
        Request $request,
        RoundingService $rounding
    ) {
        $this->form    = $form;
        $this->manager = $manager;
        $this->request = $request;
        $this->rounding = $rounding;

    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    public function process(Product $product)
    {
        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);
            $formData = $this->form->getData();

            foreach ($formData as $key => $data) {
                $warehouseInventoryLevel = $this->getWarehouseInventoryLevelObject($key, $data);

                if (!$warehouseInventoryLevel->getQuantity()) {
                    $this->manager->remove($warehouseInventoryLevel);
                }

                if (!$warehouseInventoryLevel->getId()) {
                        $this->manager->persist($warehouseInventoryLevel);
                }
            }

            $this->manager->flush();

            return true;
        }

        return false;
    }

    /**
     * @param string    $key
     * @param array     $data
     *
     * @return object|WarehouseInventoryLevel
     */
    protected function getWarehouseInventoryLevelObject($key, array $data)
    {
        list($warehouseId, $precisionId) = explode('_', $key);

        $quantity = (float) $data['data']['levelQuantity'];

        $warehouse = $this->manager->getRepository('OroB2BWarehouseBundle:Warehouse')->find($warehouseId);

        if (!$warehouse) {
            throw new \RuntimeException(sprintf('Warehouse with Id: %s doesn\'exist', $warehouseId));
        }

        $unitPrecision = $this->manager->getRepository('OroB2BProductBundle:ProductUnitPrecision')->find($precisionId);

        if (!$unitPrecision) {
            throw new \RuntimeException(sprintf('Product Unit Precision with Id: %s doesn\'exist', $precisionId));
        }

        $quantity = $this->rounding->round($quantity, $unitPrecision->getPrecision());

        $warehouseInventoryLevel = $this->manager->getRepository('OroB2BWarehouseBundle:WarehouseInventoryLevel')
            ->findOneBy(['warehouse' => $warehouse, 'productUnitPrecision' => $unitPrecision]);

        if (!$warehouseInventoryLevel) {
            $warehouseInventoryLevel = new WarehouseInventoryLevel();
            $warehouseInventoryLevel
                ->setWarehouse($warehouse)
                ->setProductUnitPrecision($unitPrecision)
            ;
        }

        $warehouseInventoryLevel->setQuantity($quantity);

        return $warehouseInventoryLevel;
    }
}
