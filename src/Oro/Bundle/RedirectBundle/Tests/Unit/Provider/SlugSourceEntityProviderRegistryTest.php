<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderRegistry;

class SlugSourceEntityProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSourceEntityBySlugWithEmptyProviders()
    {
        $slug = new Slug();
        $registry = new SlugSourceEntityProviderRegistry([]);
        $this->assertNull($registry->getSourceEntityBySlug($slug));
    }

    public function testGetSourceEntityBySlugWithWrongProviders()
    {
        $slug = new Slug();
        $provider = $this->createMock(SlugSourceEntityProviderInterface::class);
        $provider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($slug)
            ->willReturn(null);
        $registry = new SlugSourceEntityProviderRegistry([$provider]);
        $this->assertNull($registry->getSourceEntityBySlug($slug));
    }

    public function testGetSourceEntityBySlug()
    {
        $sourceEntity = $this->createMock(SlugAwareInterface::class);
        $slug = new Slug();
        $provider = $this->createMock(SlugSourceEntityProviderInterface::class);
        $provider->expects($this->once())
            ->method('getSourceEntityBySlug')
            ->with($slug)
            ->willReturn($sourceEntity);
        $registry = new SlugSourceEntityProviderRegistry([$provider]);
        $this->assertSame($sourceEntity, $registry->getSourceEntityBySlug($slug));
    }
}
