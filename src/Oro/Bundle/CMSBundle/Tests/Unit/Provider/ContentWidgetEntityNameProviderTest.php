<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetEntityNameProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

class ContentWidgetEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetEntityNameProvider */
    private $provider;

    /** @var ContentWidget */
    private $contentWidget;

    protected function setUp(): void
    {
        $this->provider = new ContentWidgetEntityNameProvider();

        $this->contentWidget = new ContentWidget();
        $this->contentWidget->setName('test name');
    }

    public function testGetNameForShortFormat(): void
    {
        $this->assertFalse($this->provider->getName(EntityNameProviderInterface::SHORT, 'en', $this->contentWidget));
        $this->assertFalse($this->provider->getName(null, 'en', $this->contentWidget));
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals(
            $this->contentWidget->getName(),
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $this->contentWidget)
        );
    }

    public function testGetNameDQL(): void
    {
        $this->assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', ContentWidget::class, 'contentWidget')
        );
    }
}
