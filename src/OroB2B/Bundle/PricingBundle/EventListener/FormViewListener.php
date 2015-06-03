<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

class FormViewListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCustomerView(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $customerId = $this->request->get('id');
        /** @var Customer $customer */
        $customer = $this->doctrineHelper->getEntityReference('OroB2BCustomerBundle:Customer', $customerId);
        $priceList = $this->getPriceListRepository()->getPriceListByCustomer($customer);

        if ($priceList) {
            $template = $event->getEnvironment()->render(
                'OroB2BPricingBundle:Customer:price_list_view.html.twig',
                ['priceList' => $priceList]
            );
            $event->getScrollData()->addSubBlockData(0, 0, $template);
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCustomerGroupView(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $groupId = $this->request->get('id');
        /** @var CustomerGroup $group */
        $group = $this->doctrineHelper->getEntityReference('OroB2BCustomerBundle:CustomerGroup', $groupId);
        $priceList = $this->getPriceListRepository()->getPriceListByCustomerGroup($group);

        if ($priceList) {
            $template = $event->getEnvironment()->render(
                'OroB2BPricingBundle:Customer:price_list_view.html.twig',
                ['priceList' => $priceList]
            );
            $event->getScrollData()->addSubBlockData(0, 0, $template);
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Customer:price_list_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:PriceList');
    }
}
