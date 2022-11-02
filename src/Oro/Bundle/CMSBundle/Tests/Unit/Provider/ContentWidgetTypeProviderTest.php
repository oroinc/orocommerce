<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetTypeProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentWidgetTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ContentWidgetTypeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->provider = new ContentWidgetTypeProvider($this->contentWidgetTypeRegistry, $this->translator);
    }

    public function testGetAvailableContentWidgetTypes(): void
    {
        $type = new ContentWidgetTypeStub();

        $this->contentWidgetTypeRegistry->expects($this->once())
            ->method('getTypes')
            ->willReturn([$type]);

        $this->assertEquals(
            [$type->getLabel() . '.trans' => $type::getName()],
            $this->provider->getAvailableContentWidgetTypes()
        );
    }
}
