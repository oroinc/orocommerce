<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SaleBundle\EventListener\CustomerViewListener as BaseCustomerViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CustomerViewListener extends BaseCustomerViewListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const CUSTOMER_VIEW_TEMPLATE = 'OroRFPBundle:Customer:rfp_view.html.twig';
    const CUSTOMER_LABEL = 'oro.rfp.datagrid.customer.label';

    const CUSTOMER_USER_VIEW_TEMPLATE = 'OroRFPBundle:CustomerUser:rfp_view.html.twig';
    const CUSTOMER_USER_LABEL = 'oro.rfp.datagrid.customer_user.label';


    /**
     * {@inheritdoc}
     */
    public function onCustomerView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }
        parent::onCustomerView($event);
    }

    /**
     * {@inheritdoc}
     */
    public function onCustomerUserView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }
        parent::onCustomerUserView($event);
    }
}
