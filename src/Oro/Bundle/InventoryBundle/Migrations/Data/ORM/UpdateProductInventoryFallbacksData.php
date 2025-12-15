<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Preload fallback values for product inventory fields.
 */
final class UpdateProductInventoryFallbacksData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
    }
}
