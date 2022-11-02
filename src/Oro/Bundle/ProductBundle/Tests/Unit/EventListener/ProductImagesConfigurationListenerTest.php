<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImagesConfigurationListener;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductImagesConfigurationListenerTest extends \PHPUnit\Framework\TestCase
{
    const MESSAGE = 'message';

    /**
     * @internal
     */
    const SCOPE_APP = 'app';

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $session;

    /**
     * @var ProductImagesConfigurationListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->session = $this->createMock(Session::class);

        $this->listener = new ProductImagesConfigurationListener(
            $this->session,
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

    /**
     * @param array $settings
     *
     * @return ConfigUpdateEvent
     */
    protected function prepareEvent(array $settings)
    {
        return new ConfigUpdateEvent($settings, self::SCOPE_APP, null);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareFlashBag()
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
