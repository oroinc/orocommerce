<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class ThemeListener
{
    const FRONTEND_THEME = 'demo';

    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @var FrontendHelper
     */
    protected $helper;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @param ThemeRegistry $themeRegistry
     * @param FrontendHelper $helper
     * @param boolean $installed
     */
    public function __construct(ThemeRegistry $themeRegistry, FrontendHelper $helper, $installed)
    {
        $this->themeRegistry = $themeRegistry;
        $this->helper = $helper;
        $this->installed = $installed;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->installed) {
            return;
        }

        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        if ($this->helper->isFrontendRequest($event->getRequest())) {
            $this->themeRegistry->setActiveTheme(self::FRONTEND_THEME);
        }
    }
}
