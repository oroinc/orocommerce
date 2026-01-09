<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Update product attribute index.
 */
class UpdateProductAttributesConfigIndex extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->synchronizeProductAttributesIndexByScope('attribute');
    }
}
