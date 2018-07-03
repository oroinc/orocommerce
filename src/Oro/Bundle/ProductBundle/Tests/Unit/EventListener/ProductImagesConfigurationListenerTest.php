<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImagesConfigurationListener;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class ProductImagesConfigurationListenerTest extends \PHPUnit\Framework\TestCase
{
    const MESSAGE = 'message';

    /**
     * @internal
     */
    const SCOPE_APP = 'app';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var ProductImagesConfigurationListener
     */
    protected $listener;

    public function setUp()
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->session = $this->prophesize(Session::class);

        $this->listener = new ProductImagesConfigurationListener(
            $this->session->reveal(),
            $this->translator->reveal()
        );
    }

    public function testBeforeSaveAddsMessageForProductImagesSection()
    {
        $this->translator->trans(Argument::type('string'))->willReturn(self::MESSAGE);
        $this->session->getFlashBag()->willReturn($this->prepareFlashBag()->reveal());

        $this->listener->afterUpdate($this->prepareEvent([
            'oro_product.product_image_watermark_size' => ['old' => 20, 'new' => 21],
            'oro_product.product_image_watermark_position' => ['old' => 'center', 'new' => 'top_left'],
            'oro_product.product_image_watermark_file' => ['old' => 'file1', 'new' => 'file2']
        ]));
    }

    public function testBeforeSaveDoesNothingForNonProductImagesSection()
    {
        $this->translator->trans(Argument::type('string'))->shouldNotBeCalled();

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
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function prepareFlashBag()
    {
        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag
            ->add(
                ProductImagesConfigurationListener::MESSAGE_TYPE,
                self::MESSAGE . ' <code>' . ProductImagesConfigurationListener::COMMAND . '</code>'
            )->shouldBeCalledTimes(1);

        return $flashBag;
    }
}
