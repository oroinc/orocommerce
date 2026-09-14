<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * The batched canonical URL resolution falls back to the system URL through an entity reference,
 * which costs no query only while the routing information providers read nothing but the identifier.
 * The providers live in other bundles, so one that starts reading more would add a query per entity
 * without failing any test of its own.
 *
 * @see \Oro\Bundle\RedirectBundle\Generator\BatchCanonicalUrlGenerator::getUrls
 */
class RoutingInformationProviderTest extends WebTestCase
{
    /** the biggest value that fits into an "integer" database column */
    private const int NOT_EXISTING_ID = 2147483647;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getRoutingInformationProvider(): RoutingInformationProvider
    {
        return self::getContainer()->get('oro_redirect.provider.routing_information_provider');
    }

    public function testGetRouteDataDoesNotInitializeEntityReference(): void
    {
        $provider = $this->getRoutingInformationProvider();
        $doctrine = self::getContainer()->get('doctrine');

        $entityClasses = $provider->getEntityClasses();
        self::assertNotEmpty($entityClasses);

        foreach ($entityClasses as $entityClass) {
            $em = $doctrine->getManagerForClass($entityClass);
            $reference = $em->getReference($entityClass, self::NOT_EXISTING_ID);

            try {
                $provider->getRouteData($reference);
            } catch (EntityNotFoundException) {
                // the reference was initialized against a row that does not exist
                self::fail($this->getNotInitializedFailureMessage($entityClass));
            }

            self::assertInstanceOf(
                Proxy::class,
                $reference,
                \sprintf('A reference to "%s" is expected to be a proxy', $entityClass)
            );
            self::assertFalse(
                $reference->__isInitialized(),
                $this->getNotInitializedFailureMessage($entityClass)
            );

            $em->clear();
        }
    }

    private function getNotInitializedFailureMessage(string $entityClass): string
    {
        return \sprintf(
            '"%s" routing information provider must not initialize the entity reference,'
            . ' otherwise the batched canonical URL resolution costs a query per entity',
            $entityClass
        );
    }
}
