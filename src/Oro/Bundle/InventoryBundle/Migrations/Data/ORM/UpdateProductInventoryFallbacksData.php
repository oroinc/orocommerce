<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Preload fallback values for product inventory fields.
 * Superseded by {@see \Oro\Bundle\ProductBundle\Migrations\Data\ORM\UpdateProductFallbackData}.
 * Kept as a no-op: an installation that never executed it would otherwise run it over the whole catalog.
 */
final class UpdateProductInventoryFallbacksData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
    }
}
