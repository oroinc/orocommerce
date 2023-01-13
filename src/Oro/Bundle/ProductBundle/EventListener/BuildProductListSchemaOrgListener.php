<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;

/**
 * Event listener to add schema_org_description and schema_org_brand_name to ProductView in list item
 */
class BuildProductListSchemaOrgListener
{
    public function onBuildQueryProductList(BuildQueryProductListEvent $event): void
    {
        $event
            ->getQuery()
            ->addSelect([
                'schema_org_description_LOCALIZATION_ID as schema_org_description',
                'schema_org_brand_name_LOCALIZATION_ID as schema_org_brand_name'
            ]);
    }

    public function onBuildResultProductList(BuildResultProductListEvent $event): void
    {
        foreach ($event->getProductData() as $productData) {
            $productView = $event->getProductView($productData['id']);
            if (array_key_exists('schema_org_description', $productData)) {
                $productView->set('schemaOrgDescription', $productData['schema_org_description']);
            }
            if (array_key_exists('schema_org_brand_name', $productData)) {
                $productView->set('schemaOrgBrandName', $productData['schema_org_brand_name']);
            }
        }
    }
}
