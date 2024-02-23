<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImagesConfigurationListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductImagesConfigurationListenerTest extends \PHPUnit\Framework\TestCase
{
    protected TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    protected Session|\PHPUnit\Framework\MockObject\MockObject $session;

    protected ProductImagesConfigurationListener $listener;

    protected RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
        $this->listener = new ProductImagesConfigurationListener(
            $this->requestStack,
            $this->translator
        );
    }

    public function testBeforeSaveAddsMessageForProductImagesSection()
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($str) {
                return $str . ' TR';
            });
        $this->session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($this->prepareFlashBag());
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(self::once())
            ->method('getSession')
            ->willReturn($this->session);
        $requestMock->expects(self::exactly(2))
            ->method('hasSession')
            ->willReturn(true);
        $this->requestStack->expects(self::exactly(2))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);
        $this->listener->afterUpdate($this->prepareEvent([
            'oro_product.product_image_watermark_size' => ['old' => 20, 'new' => 21],
            'oro_product.original_file_names_enabled' => ['old' => false, 'new' => true],
            'oro_product.product_image_watermark_position' => ['old' => 'center', 'new' => 'top_left'],
            'oro_product.product_image_watermark_file' => ['old' => 'file1', 'new' => 'file2']
        ]));
    }

    public function testBeforeSaveAddsMessageForProductImagesSectionFileNames()
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($str) {
                return $str . ' TR';
            });

        $flashBag = $this->createMock(FlashBag::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with(
                ProductImagesConfigurationListener::MESSAGE_TYPE,
                ProductImagesConfigurationListener::SPACE_NOTICE_TEXT_TRANS_KEY . ' TR'
            );

        $this->session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(self::once())
            ->method('getSession')
            ->willReturn($this->session);
        $requestMock->expects($this->once())
            ->method('hasSession')
            ->willReturn(true);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->listener->afterUpdate($this->prepareEvent([
            'oro_product.original_file_names_enabled' => ['old' => false, 'new' => true]
        ]));
    }

    public function testBeforeSaveDoesNothingForNonProductImagesSection()
    {
        $this->translator->expects($this->never())
            ->method('trans');

        $this->listener->afterUpdate($this->prepareEvent([
            'oro_product.product_unit' => ['old' => 'something', 'new' => 'saomething2'],
        ]));
    }

    protected function prepareEvent(array $settings): ConfigUpdateEvent
    {
        return new ConfigUpdateEvent($settings, 'global', 0);
    }

    protected function prepareFlashBag(): FlashBag
    {
        $flashBag = $this->createMock(FlashBag::class);
        $flashBag->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    ProductImagesConfigurationListener::MESSAGE_TYPE,
                    ProductImagesConfigurationListener::NOTICE_TEXT_TRANS_KEY
                    . ' TR <code>' . ProductImagesConfigurationListener::COMMAND . '</code>'
                ],
                [
                    ProductImagesConfigurationListener::MESSAGE_TYPE,
                    ProductImagesConfigurationListener::SPACE_NOTICE_TEXT_TRANS_KEY . ' TR'
                ]
            );

        return $flashBag;
    }
}
