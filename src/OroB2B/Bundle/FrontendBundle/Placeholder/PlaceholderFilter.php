<?php

namespace OroB2B\Bundle\FrontendBundle\Placeholder;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActivityListBundle\Placeholder\PlaceholderFilter as BasePlaceholderFilter;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class PlaceholderFilter extends BasePlaceholderFilter
{

    /**
     * @var FrontendHelper
     */
    protected $helper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity = null, $pageType = null)
    {
        if ($this->isFrontendRoute()) {
            return false;
        }
        return parent::isApplicable($entity, $pageType);
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowedOnPage($entity, $pageType)
    {
        if ($this->isFrontendRoute()) {
            return false;
        }
        return parent::isAllowedOnPage($entity, $pageType);
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowedButton(BeforeGroupingChainWidgetEvent $event)
    {

        if ($this->isFrontendRoute()) {
            // Clear allowed widgets
            $event->setWidgets([]);
        }
        parent::isAllowedButton($event);
    }

    /**
     * @return bool
     */
    public function isFrontendRoute()
    {
        if (!$this->requestStack || !$this->requestStack->getCurrentRequest()) {
            return false;
        }

        return $this->helper->isFrontendRequest($this->requestStack->getCurrentRequest());
    }

    /**
     * @param FrontendHelper $helper
     */
    public function setHelper(FrontendHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param RequestStack|null $requestStack
     */
    public function setRequestStack(RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack->getCurrentRequest();
    }
}
