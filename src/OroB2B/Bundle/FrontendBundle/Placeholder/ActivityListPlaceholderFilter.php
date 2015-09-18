<?php

namespace OroB2B\Bundle\FrontendBundle\Placeholder;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActivityListBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class ActivityListPlaceholderFilter
{
    /**
     * @var PlaceholderFilter
     */
    protected $filter;

    /**
     * @var FrontendHelper
     */
    protected $helper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param PlaceholderFilter $filter
     * @param FrontendHelper $helper
     * @param RequestStack $requestStack
     */
    public function __construct(PlaceholderFilter $filter, FrontendHelper $helper, RequestStack $requestStack)
    {
        $this->filter = $filter;
        $this->helper = $helper;
        $this->requestStack = $requestStack;
    }

    /**
     * @param object|null $entity
     * @param int|null $pageType
     * @return bool
     */
    public function isApplicable($entity = null, $pageType = null)
    {
        if ($this->isFrontendRoute()) {
            return false;
        }

        return $this->filter->isApplicable($entity, $pageType);
    }

    /**
     * @param BeforeGroupingChainWidgetEvent $event
     */
    public function isAllowedButton(BeforeGroupingChainWidgetEvent $event)
    {
        if ($this->isFrontendRoute()) {
            // Clear allowed widgets
            $event->setWidgets([]);
            $event->stopPropagation();
        } else {
            $this->filter->isAllowedButton($event);
        }
    }

    /**
     * @return bool
     */
    protected function isFrontendRoute()
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            return false;
        }

        return $this->helper->isFrontendRequest($currentRequest);
    }
}
