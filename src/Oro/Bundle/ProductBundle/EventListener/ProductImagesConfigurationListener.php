<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Warn user about watermark and original file names options impact on change.
 */
class ProductImagesConfigurationListener
{
    public const PRODUCT_IMAGE_WATERMARK_SECTION_PREFIX = 'oro_product.product_image_watermark';
    public const PRODUCT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_product.original_file_names_enabled';
    public const NOTICE_TEXT_TRANS_KEY = 'oro.product.system_configuration.notice.product_image_watermark';
    public const SPACE_NOTICE_TEXT_TRANS_KEY = 'oro.product.system_configuration.notice.storage_check_space';
    public const COMMAND = 'php bin/console product:image:resize-all --force';
    public const MESSAGE_TYPE = 'warning';

    /**
     * @var bool
     */
    private $spaceWarningAdded = false;

    public function __construct(
        private RequestStack $requestStack,
        protected TranslatorInterface $translator
    ) {
    }

    public function afterUpdate(ConfigUpdateEvent $event)
    {
        $changeSet = $event->getChangeSet();
        foreach ($changeSet as $configKey => $change) {
            if (str_contains($configKey, self::PRODUCT_IMAGE_WATERMARK_SECTION_PREFIX)) {
                $request = $this->requestStack->getCurrentRequest();
                if (null !== $request && $request->hasSession()) {
                    $this->requestStack->getSession()->getFlashBag()->add(self::MESSAGE_TYPE, $this->getNotice($event));
                }
                $this->addSpaceWarning();
                break;
            }
            if ($configKey === self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED) {
                $this->addSpaceWarning();
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

    private function addSpaceWarning()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$this->spaceWarningAdded && null !== $request && $request->hasSession()) {
            $request->getSession()->getFlashBag()->add(
                self::MESSAGE_TYPE,
                $this->translator->trans(self::SPACE_NOTICE_TEXT_TRANS_KEY)
            );
            $this->spaceWarningAdded = true;
        }
    }
}
