<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductImagesConfigurationListener
{
    const PRODUCT_IMAGE_WATERMARK_SECTION_PREFIX = 'oro_product.product_image_watermark';
    const NOTICE_TEXT_TRANS_KEY = 'oro.product.system_configuration.notice.product_image_watermark';
    const COMMAND = 'php bin/console product:image:resize-all --force';
    const MESSAGE_TYPE = 'warning';

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
     * @param ConfigUpdateEvent $event
     */
    public function afterUpdate(ConfigUpdateEvent $event)
    {
        $changeSet = $event->getChangeSet();
        foreach ($changeSet as $configKey => $change) {
            if (false !== strpos($configKey, self::PRODUCT_IMAGE_WATERMARK_SECTION_PREFIX)) {
                $this->session->getFlashBag()->add(self::MESSAGE_TYPE, $this->getNotice($event));

                return;
            }
        }
    }

    /**
     * @param ConfigUpdateEvent $event
     *
     * @return string
     */
    protected function getNotice(ConfigUpdateEvent $event)
    {
        return sprintf(
            '%s <code>%s</code>',
            $this->translator->trans(self::NOTICE_TEXT_TRANS_KEY),
            self::COMMAND
        );
    }
}
