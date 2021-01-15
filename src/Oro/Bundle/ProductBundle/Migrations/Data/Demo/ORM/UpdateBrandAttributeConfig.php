<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class UpdateBrandAttributeConfig extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /**
     * {@inheritDoc}
     */
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
