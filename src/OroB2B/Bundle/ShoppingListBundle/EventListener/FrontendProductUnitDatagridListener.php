<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductUnitDatagridListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param ProductUnitLabelFormatter $productUnitLabelFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        ProductUnitLabelFormatter $productUnitLabelFormatter
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->productUnitLabelFormatter = $productUnitLabelFormatter;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $path = '[columns]';
        $select = $config->offsetGetByPath($path);
        $select['units'] = [
            'label' => $this->translator->trans('orob2b.product.productunit.entity_label'),
            'frontend_type' => PropertyInterface::TYPE_ARRAY,
        ];
        $config->offsetSetByPath($path, $select);
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroB2BProductBundle:Product');
        $products = $repository->getProductsWithUnits(array_map(function (ResultRecord $record) {
            return $record->getValue('id');
        }, $records));

        $productsWithUnits = array_reduce($products, function ($result, Product $product) {
            $unitPrecisions = $product->getUnitPrecisions();
            $result[$product->getId()] = $unitPrecisions->map(function (ProductUnitPrecision $unitPrecision) {
                return $unitPrecision->getUnit();
            })->toArray();
            return $result;
        }, []);

        foreach ($records as $record) {
            $productUnits = $productsWithUnits[$record->getValue('id')];
            $record->addData(['units' => $this->productUnitLabelFormatter->formatChoices($productUnits)]);
        }
    }
}
