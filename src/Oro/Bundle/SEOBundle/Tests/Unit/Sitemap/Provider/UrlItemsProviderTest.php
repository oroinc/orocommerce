<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Event\UrlItemsProviderEvent;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UrlItemsProviderTest extends OrmTestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $canonicalUrlGenerator;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var UrlItemsProvider */
    private $urlItemsProvider;

    protected function setUp(): void
    {
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);

        $this->urlItemsProvider = new UrlItemsProvider(
            $this->canonicalUrlGenerator,
            $this->configManager,
            $this->eventDispatcher,
            $doctrine
        );
        $this->urlItemsProvider->setEntityClass(Product::class);
        $this->urlItemsProvider->setType('test');
        $this->urlItemsProvider->setChangeFrequencySettingsKey('sk_cf');
        $this->urlItemsProvider->setPrioritySettingsKey('sk_priority');
    }

    public function testItDispatchEvent()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = '1';

        $this->eventDispatcher->expects($this->exactly(6))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->isInstanceOf(UrlItemsProviderEvent::class),
                    UrlItemsProviderEvent::ON_START . '.test'
                ],
                [
                    $this->isInstanceOf(UrlItemsProviderEvent::class),
                    UrlItemsProviderEvent::ON_START
                ],
                [
                    $this->isInstanceOf(RestrictSitemapEntitiesEvent::class),
                    RestrictSitemapEntitiesEvent::NAME . '.test'
                ],
                [
                    $this->isInstanceOf(RestrictSitemapEntitiesEvent::class),
                    RestrictSitemapEntitiesEvent::NAME
                ],
                [
                    $this->isInstanceOf(UrlItemsProviderEvent::class),
                    UrlItemsProviderEvent::ON_END . '.test'
                ],
                [
                    $this->isInstanceOf(UrlItemsProviderEvent::class),
                    UrlItemsProviderEvent::ON_END
                ]
            );

        $this->urlItemsProvider->getUrlItems($website, $version)->current();
    }
}
