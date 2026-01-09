<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Oro\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Updates brand product attribute configuration for demo data.
 */
class UpdateBrandAttributeConfig extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->updateProductAttributes(
            [
                'brand' => [
                    'filterable' => true,
                ],
            ]
        );
        $this->getConfigManager()->flush();
    }
}
