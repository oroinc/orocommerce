<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\ProductBundle\EventListener\WebpAwareProductAutocompleteListener;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\UIBundle\Tools\UrlHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebpAwareProductAutocompleteListenerTest extends \PHPUnit\Framework\TestCase
{
    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    private ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject $imagePlaceholderProvider;

    private UrlHelper|\PHPUnit\Framework\MockObject\MockObject $urlHelper;

    protected function setUp(): void
    {
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->urlHelper
            ->expects(self::any())
            ->method('getAbsolutePath')
            ->willReturnCallback(static fn (string $path) => '/absolute' . $path);
    }

    /**
     * @dataProvider getOnCollectAutocompleteFieldsDataProvider
     */
    public function testOnCollectAutocompleteFields(bool $webpSupported, array $expectedFields): void
    {
        $event = new CollectAutocompleteFieldsEvent([]);

        $this->webpConfiguration->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn($webpSupported);

        $listener = new WebpAwareProductAutocompleteListener(
            $this->webpConfiguration,
            $this->imagePlaceholderProvider,
            $this->urlHelper
        );
        $listener->onCollectAutocompleteFields($event);

        self::assertEquals(
            $expectedFields,
            $event->getFields()
        );
    }

    public function getOnCollectAutocompleteFieldsDataProvider(): array
    {
        return [
            [
                'webpSupported' => false,
                'expectedFields' => [],
            ],
            [
                'webpSupported' => true,
                'expectedFields' => ['text.image_product_small_webp as imageWebp'],
            ],
        ];
    }

    public function testOnProcessAutocompleteDataDefaultImage(): void
    {
        $data = [
            'products' => [
                [],
                ['imageWebp' => ''],
            ]
        ];
        $event = new ProcessAutocompleteDataEvent($data, 'request', new Result(new Query()));

        $requestStack = new RequestStack();
        $request = Request::create('https://localhost/');
        $requestStack->push($request);

        $defaultImageUrl = '/product_small_webp/no_image';
        $this->imagePlaceholderProvider->expects(self::once())
            ->method('getPath')
            ->with('product_small', 'webp')
            ->willReturn($defaultImageUrl);

        $listener = new WebpAwareProductAutocompleteListener(
            $this->webpConfiguration,
            $this->imagePlaceholderProvider,
            $this->urlHelper
        );
        $listener->onProcessAutocompleteData($event);

        $expectedData = $data;
        $expectedData['products'][1]['imageWebp'] = $defaultImageUrl;

        self::assertEquals($expectedData, $event->getData());
    }

    public function testOnProcessAutocompleteData(): void
    {
        $imageUrl = '/image/webp';
        $data = [
            'products' => [
                [],
                ['imageWebp' => $imageUrl],
            ]
        ];
        $event = new ProcessAutocompleteDataEvent($data, 'request', new Result(new Query()));

        $listener = new WebpAwareProductAutocompleteListener(
            $this->webpConfiguration,
            $this->imagePlaceholderProvider,
            $this->urlHelper
        );
        $listener->onProcessAutocompleteData($event);

        $expectedData = $data;
        $expectedData['products'][1]['imageWebp'] = '/absolute' . $imageUrl;

        self::assertEquals($expectedData, $event->getData());
    }
}
