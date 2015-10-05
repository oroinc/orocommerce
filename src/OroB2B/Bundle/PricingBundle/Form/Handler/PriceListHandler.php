<?php

namespace OroB2B\Bundle\PricingBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param RequestStack $requestStack
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, RequestStack $requestStack, ObjectManager $manager)
    {
        $this->form = $form;
        $this->requestStack = $requestStack;
        $this->manager = $manager;
    }

    /**
     * @param PriceList $priceList
     * @return boolean
     */
    public function process(PriceList $priceList)
    {
        $this->form->setData($priceList);
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($request);

            if ($this->form->isValid()) {
                $this->onSuccess(
                    $priceList,
                    $this->form->get('appendAccounts')->getData(),
                    $this->form->get('removeAccounts')->getData(),
                    $this->form->get('appendAccountGroups')->getData(),
                    $this->form->get('removeAccountGroups')->getData(),
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
     * @param Account[] $appendAccounts
     * @param Account[] $removeAccounts
     * @param AccountGroup[] $appendAccountGroups
     * @param AccountGroup[] $removeAccountGroups
     * @param Website[] $appendWebsites
     * @param Website[] $removeWebsites
     */
    protected function onSuccess(
        PriceList $entity,
        array $appendAccounts,
        array $removeAccounts,
        array $appendAccountGroups,
        array $removeAccountGroups,
        array $appendWebsites,
        array $removeWebsites
    ) {
        $this->manager->persist($entity);

        // first stage - remove relations to entities, used to prevent unique key conflicts
        $this->setPriceListToAccounts(array_merge($appendAccounts, $removeAccounts));
        $this->setPriceListToAccountGroups(array_merge($appendAccountGroups, $removeAccountGroups));
        $this->setPriceListToWebsites(array_merge($appendWebsites, $removeWebsites));

        $this->manager->flush();

        // second stage - set correct relations
        $this->setPriceListToAccounts($appendAccounts, $entity);
        $this->setPriceListToAccountGroups($appendAccountGroups, $entity);
        $this->setPriceListToWebsites($appendWebsites, $entity);

        $this->manager->flush();
    }

    /**
     * @param Account[] $accounts
     * @param PriceList|null $priceList
     */
    protected function setPriceListToAccounts(array $accounts, PriceList $priceList = null)
    {
        $repository = $this->getPriceListRepository();

        foreach ($accounts as $account) {
            $repository->setPriceListToAccount($account, $priceList);
        }
    }

    /**
     * @param AccountGroup[] $accountGroups
     * @param PriceList|null $priceList
     */
    protected function setPriceListToAccountGroups(array $accountGroups, PriceList $priceList = null)
    {
        $repository = $this->getPriceListRepository();

        foreach ($accountGroups as $accountGroup) {
            $repository->setPriceListToAccountGroup($accountGroup, $priceList);
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
