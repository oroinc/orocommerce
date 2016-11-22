<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

class ProductImagesConfigurationListener
{
    const PRODUCT_IMAGE_WATERMARK_SECTION_PREFIX = 'oro_product.product_image_watermark';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Session $session
     * @param TranslatorInterface $translator
     */
    public function __construct(Session $session, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        foreach ($event->getSettings() as $configKey => $setting) {
            if (false !== strpos($configKey, self::PRODUCT_IMAGE_WATERMARK_SECTION_PREFIX)) {
                $this->session->getFlashBag()->add(
                    'info',
                    $this->translator->trans('oro.product.system_configuration.notice.product_image_watermark')
                );

                return;
            }
        }
    }
}
