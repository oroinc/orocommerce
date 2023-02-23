<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetTypeProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;

class ContentWidgetTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetTypeRegistry;

    /** @var ContentWidgetTypeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);

        $this->provider = new ContentWidgetTypeProvider($this->contentWidgetTypeRegistry);
    }

    public function testGetAvailableContentWidgetTypes(): void
    {
        $type = new ContentWidgetTypeStub();

        $this->contentWidgetTypeRegistry->expects($this->once())
            ->method('getTypes')
            ->willReturn([$type]);

        $this->assertEquals(
            [$type->getLabel() => $type::getName()],
            $this->provider->getAvailableContentWidgetTypes()
        );
    }
}
