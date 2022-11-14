<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Update product attribute index.
 */
class UpdateProductAttributesConfigIndex extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    public function load(ObjectManager $manager)
    {
        $this->synchronizeProductAttributesIndexByScope('attribute');
    }
}
