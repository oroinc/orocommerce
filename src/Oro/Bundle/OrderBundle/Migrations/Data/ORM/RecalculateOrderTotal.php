<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Recalculate order total price and save it in serialized data if it's not correct calculated.
 *
 * Note: This file kept to avoid BC breaks.
 */
class RecalculateOrderTotal extends AbstractFixture implements VersionedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getVersion(): string
    {
        return '1.0';
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
    }
}
