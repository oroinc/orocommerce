<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Data migration to make specified attributes available for export.
 */
class EnableAttributesUsableInExport extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->updateProductAttributes(
            [
                'sku' => [
                    'use_in_export' => true,
                ],
                'names' => [
                    'use_in_export' => false,
                ],
                'inventory_status' => [
                    'use_in_export' => true,
                ],
            ]
        );
    }
}
