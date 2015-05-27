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

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $appendCustomers      = $this->form->get('appendCustomers')->getData();
                $removeCustomers      = $this->form->get('removeCustomers')->getData();
                $appendCustomerGroups = $this->form->get('appendCustomerGroups')->getData();
                $removeCustomerGroups = $this->form->get('removeCustomerGroups')->getData();
                $appendWebsites       = $this->form->get('appendWebsites')->getData();
                $removeWebsites       = $this->form->get('removeWebsites')->getData();

                $this->onSuccess(
                    $priceList,
                    $appendCustomers,
                    $removeCustomers,
                    $appendCustomerGroups,
                    $removeCustomerGroups,
                    $appendWebsites,
                    $removeWebsites
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
        $this->setPriceList($entity, $appendCustomers);
        $this->removePriceList($entity, $removeCustomers);
        $this->setPriceList($entity, $appendCustomerGroups);
        $this->removePriceList($entity, $removeCustomerGroups);
        $this->setPriceList($entity, $appendWebsites);
        $this->removePriceList($entity, $removeWebsites);

        $this->manager->persist($entity);
        $this->manager->flush();
    }

    /**
     * @param PriceList                            $priceList
     * @param Customer[]|CustomerGroup[]|Website[] $entities
     */
    protected function setPriceList(PriceList $priceList, array $entities)
    {
        foreach ($entities as $entity) {
            $entity->setPriceList($priceList);

            $method = 'add' . (new \ReflectionClass($entity))->getShortName();
            $priceList->$method($entity);
        }
    }

    /**
     * @param PriceList                            $priceList
     * @param Customer[]|CustomerGroup[]|Website[] $entities
     */
    protected function removePriceList(PriceList $priceList, array $entities)
    {
        foreach ($entities as $entity) {
            if ($entity->getPriceList()->getId() === $priceList->getId()) {
                $entity->setPriceList(null);

                $method = 'remove' . (new \ReflectionClass($entity))->getShortName();
                $priceList->$method($entity);
            }
        }
    }
}
