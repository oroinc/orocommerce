<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Handler;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Handler\RequestContentVariantHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestContentVariantHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var RequestContentVariantHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->handler = new RequestContentVariantHandler($this->requestStack);
    }

    public function testGetCategoryContentVariantIdNoRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->assertFalse($this->handler->getContentVariantId());
    }

    public function testGetCategoryContentVariantIdIsBool()
    {
        $request = new Request([ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => false]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertFalse($this->handler->getContentVariantId());
    }

    public function testGetCategoryContentVariantIdZero()
    {
        $request = new Request([ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => 0]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertFalse($this->handler->getContentVariantId());
    }

    public function testGetCategoryContentVariantId()
    {
        $value = 777;
        $request = new Request([ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => $value]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals($value, $this->handler->getContentVariantId());
    }

    /**
     * @dataProvider overrideVariantConfigurationDataProvider
     */
    public function testGetOverrideVariantConfiguration(string|int|bool|null $value, bool $expected)
    {
        $request = new Request([ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => $value]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $actual = $this->handler->getOverrideVariantConfiguration();
        $this->assertEquals($expected, $actual);
    }

    public function overrideVariantConfigurationDataProvider(): array
    {
        return [
            [
                'value' => true,
                'expected' => true,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => 'true',
                'expected' => true,
            ],
            [
                'value' => 'false',
                'expected' => false,
            ],
            [
                'value' => 1,
                'expected' => true,
            ],
            [
                'value' => 0,
                'expected' => false,
            ],
            [
                'value' => -1,
                'expected' => false,
            ],
            [
                'value' => '1',
                'expected' => true,
            ],
            [
                'value' => '0',
                'expected' => false,
            ],
            [
                'value' => '-1',
                'expected' => false,
            ],
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => 'test',
                'expected' => false,
            ],
        ];
    }
}
