<?php

namespace OroB2B\Bundle\PricingBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

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
        $this->manager->persist($entity);

        // first stage - remove relations to entities, used to prevent unique key conflicts
        $this->setPriceListToCustomers(null, $appendCustomers);
        $this->removePriceListFromCustomers($entity, $removeCustomers);

        $this->setPriceListToCustomerGroups(null, $appendCustomerGroups);
        $this->removePriceListFromCustomerGroups($entity, $removeCustomerGroups);

        $this->setPriceListToWebsites(null, $appendWebsites);
        $this->removePriceListFromWebsites($entity, $removeWebsites);

        $this->manager->flush();

        // second stage - set correct relations
        $this->setPriceListToCustomers($entity, $appendCustomers);
        $this->setPriceListToCustomerGroups($entity, $appendCustomerGroups);
        $this->setPriceListToWebsites($entity, $appendWebsites);

        $this->manager->flush();
    }

    /**
     * @param PriceList|null $priceList
     * @param array $customers
     */
    protected function setPriceListToCustomers($priceList, array $customers)
    {
        $repository = $this->getPriceListRepository();

        /** @var Customer[] $customers */
        foreach ($customers as $customer) {
            $repository->setPriceListToCustomer($customer, $priceList);
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
            $priceList->removeCustomer($customer);
        }
    }

    /**
     * @param PriceList|null $priceList
     * @param array $customerGroups
     */
    protected function setPriceListToCustomerGroups($priceList, array $customerGroups)
    {
        $repository = $this->getPriceListRepository();

        /** @var CustomerGroup[] $customerGroups */
        foreach ($customerGroups as $customerGroup) {
            $repository->setPriceListToCustomerGroup($customerGroup, $priceList);
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
            $priceList->removeCustomerGroup($customerGroup);
        }
    }

    /**
     * @param PriceList|null $priceList
     * @param array $websites
     */
    protected function setPriceListToWebsites($priceList, array $websites)
    {
        $repository = $this->getPriceListRepository();

        /** @var Website[] $websites */
        foreach ($websites as $website) {
            $repository->setPriceListToWebsite($website, $priceList);
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
            $priceList->removeWebsite($website);
        }
    }

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        return $this->manager->getRepository('OroB2BPricingBundle:PriceList');
    }
}
