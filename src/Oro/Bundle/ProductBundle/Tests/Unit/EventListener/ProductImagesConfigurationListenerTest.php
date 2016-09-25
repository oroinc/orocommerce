<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Prophecy\Argument;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImagesConfigurationListener;

class ProductImagesConfigurationListenerTest extends \PHPUnit_Framework_TestCase
{
    const MESSAGE = 'message';

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
        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add('info', self::MESSAGE)->shouldBeCalledTimes(1);

        $this->translator->trans(Argument::type('string'))->willReturn(self::MESSAGE);
        $this->session->getFlashBag()->willReturn($flashBag->reveal());

        $this->listener->beforeSave($this->prepareEvent([
            'oro_product.product_image_watermark_size' => 20,
            'oro_product.product_image_watermark_position' => 'center',
            'oro_product.product_image_watermark_file' => 'file'
        ]));
    }

    public function testBeforeSaveDoesNothingForNonProductImagesSection()
    {
        $this->translator->trans(Argument::type('string'))->shouldNotBeCalled();

        $this->listener->beforeSave($this->prepareEvent([
            'oro_product.product_unit' => 'something',
        ]));
    }

    /**
     * @param array $settings
     * @return ConfigSettingsUpdateEvent
     */
    protected function prepareEvent(array $settings)
    {
        $configManager = $this->prophesize(ConfigManager::class);

        return new ConfigSettingsUpdateEvent($configManager->reveal(), $settings);
    }
}
