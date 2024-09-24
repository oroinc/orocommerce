<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\RequestPageProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestPageProviderTest extends TestCase
{
    private RequestPageProvider $requestPageProvider;

    private RequestStack&MockObject $requestStack;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->requestPageProvider = new RequestPageProvider($this->requestStack);
    }

    public function testWhenRequestNotFound(): void
    {
        self::assertNull($this->requestPageProvider->getPage());
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testThatAttributeReturned(ParameterBag $attributes, ?Page $expected): void
    {
        $request = new Request();
        $request->attributes = $attributes;

        $this->requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        self::assertEquals($expected, $this->requestPageProvider->getPage());
    }

    private function attributesDataProvider(): array
    {
        $page = new Page();

        return [
            'with page attr' => [new ParameterBag(['page' => $page]), $page],
            'no page attr' => [new ParameterBag(), null],
        ];
    }
}
