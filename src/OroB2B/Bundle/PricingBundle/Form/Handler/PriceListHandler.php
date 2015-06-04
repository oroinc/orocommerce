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
     * @param PriceList $entity
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
        $this->setPriceListToCustomers(array_merge($appendCustomers, $removeCustomers));
        $this->setPriceListToCustomerGroups(array_merge($appendCustomerGroups, $removeCustomerGroups));
        $this->setPriceListToWebsites(array_merge($appendWebsites, $removeWebsites));

        $this->manager->flush();

        // second stage - set correct relations
        $this->setPriceListToCustomers($appendCustomers, $entity);
        $this->setPriceListToCustomerGroups($appendCustomerGroups, $entity);
        $this->setPriceListToWebsites($appendWebsites, $entity);

        $this->manager->flush();
    }

    /**
     * @param Customer[] $customers
     * @param PriceList|null $priceList
     */
    protected function setPriceListToCustomers(array $customers, PriceList $priceList = null)
    {
        $repository = $this->getPriceListRepository();

        foreach ($customers as $customer) {
            $repository->setPriceListToCustomer($customer, $priceList);
        }
    }

    /**
     * @param CustomerGroup[] $customerGroups
     * @param PriceList|null $priceList
     */
    protected function setPriceListToCustomerGroups(array $customerGroups, PriceList $priceList = null)
    {
        $repository = $this->getPriceListRepository();

        foreach ($customerGroups as $customerGroup) {
            $repository->setPriceListToCustomerGroup($customerGroup, $priceList);
        }
    }

    /**
     * @param Website[] $websites
     * @param PriceList|null $priceList
     */
    protected function setPriceListToWebsites(array $websites, PriceList $priceList = null)
    {
        $repository = $this->getPriceListRepository();

        foreach ($websites as $website) {
            $repository->setPriceListToWebsite($website, $priceList);
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
