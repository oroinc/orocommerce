<?php

namespace OroB2B\Bundle\PricingBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param PriceList $priceList
     * @return boolean
     */
    public function process(PriceList $priceList)
    {
        $this->form->setData($priceList);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess(
                    $priceList,
                    $this->form->get('appendCustomers')->getData(),
                    $this->form->get('removeCustomers')->getData(),
                    $this->form->get('appendCustomerGroups')->getData(),
                    $this->form->get('removeCustomerGroups')->getData(),
                    $this->form->get('appendWebsites')->getData(),
                    $this->form->get('removeWebsites')->getData()
                );

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param PriceList  $entity
     * @param Customer[] $appendCustomers
     * @param Customer[] $removeCustomers
     * @param CustomerGroup[] $appendCustomerGroups
     * @param CustomerGroup[] $removeCustomerGroups
     * @param Website[] $appendWebsites
     * @param Website[] $removeWebsites
     */
    protected function onSuccess(
        PriceList $entity,
        array $appendCustomers,
        array $removeCustomers,
        array $appendCustomerGroups,
        array $removeCustomerGroups,
        array $appendWebsites,
        array $removeWebsites
    ) {
        $this->setPriceListToCustomers($entity, $appendCustomers);
        $this->removePriceListFromCustomers($entity, $removeCustomers);

        $this->setPriceListToCustomerGroups($entity, $appendCustomerGroups);
        $this->removePriceListFromCustomerGroups($entity, $removeCustomerGroups);

        $this->setPriceListToWebsites($entity, $appendWebsites);
        $this->removePriceListFromWebsites($entity, $removeWebsites);

        $this->manager->persist($entity);
        $this->manager->flush();
    }

    /**
     * @param PriceList $priceList
     * @param array $customers
     */
    protected function setPriceListToCustomers(PriceList $priceList, array $customers)
    {
        /** @var Customer[] $customers */
        foreach ($customers as $customer) {
            $customer->setPriceList($priceList);
            $priceList->addCustomer($customer);
        }
    }

    /**
     * @param PriceList $priceList
     * @param array $customers
     */
    protected function removePriceListFromCustomers(PriceList $priceList, array $customers)
    {
        /** @var Customer[] $customers */
        foreach ($customers as $customer) {
            if ($customer->getPriceList()->getId() === $priceList->getId()) {
                $customer->setPriceList(null);
                $priceList->removeCustomer($customer);
            }
        }
    }

    /**
     * @param PriceList $priceList
     * @param array $customerGroups
     */
    protected function setPriceListToCustomerGroups(PriceList $priceList, array $customerGroups)
    {
        /** @var CustomerGroup[] $customerGroups */
        foreach ($customerGroups as $customerGroup) {
            $customerGroup->setPriceList($priceList);
            $priceList->addCustomerGroup($customerGroup);
        }
    }

    /**
     * @param PriceList $priceList
     * @param array $customerGroups
     */
    protected function removePriceListFromCustomerGroups(PriceList $priceList, array $customerGroups)
    {
        /** @var CustomerGroup[] $customerGroups */
        foreach ($customerGroups as $customerGroup) {
            if ($customerGroup->getPriceList()->getId() === $priceList->getId()) {
                $customerGroup->setPriceList(null);
                $priceList->removeCustomerGroup($customerGroup);
            }
        }
    }

    /**
     * @param PriceList $priceList
     * @param array $websites
     */
    protected function setPriceListToWebsites(PriceList $priceList, array $websites)
    {
        /** @var Website[] $websites */
        foreach ($websites as $website) {
            $website->setPriceList($priceList);
            $priceList->addWebsite($website);
        }
    }

    /**
     * @param PriceList $priceList
     * @param array $websites
     */
    protected function removePriceListFromWebsites(PriceList $priceList, array $websites)
    {
        /** @var Website[] $websites */
        foreach ($websites as $website) {
            if ($website->getPriceList()->getId() === $priceList->getId()) {
                $website->setPriceList(null);
                $priceList->removeWebsite($website);
            }
        }
    }
}
