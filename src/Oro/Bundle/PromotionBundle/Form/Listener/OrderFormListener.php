<?php

namespace Oro\Bundle\PromotionBundle\Form\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\UIBundle\Route\Router;

class OrderFormListener
{
    const SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION = 'save_without_discounts_recalculation';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var AppliedDiscountManager
     */
    private $appliedDiscountManager;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var AppliedDiscountRepository
     */
    private $repository;

    /**
     * @param RequestStack $requestStack
     * @param RegistryInterface $registry
     * @param AppliedDiscountManager $appliedDiscountManager
     */
    public function __construct(
        RequestStack $requestStack,
        RegistryInterface $registry,
        AppliedDiscountManager $appliedDiscountManager
    ) {
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->appliedDiscountManager = $appliedDiscountManager;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function beforeFlush(AfterFormProcessEvent $event)
    {
        $data = $event->getData();
        if (!$data instanceof Order || !$data->getId()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request &&
            $request->get(Router::ACTION_PARAMETER) === self::SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION
        ) {
            return;
        }

        $this->getRepository()->deleteByOrder($data);
        foreach ($this->appliedDiscountManager->createAppliedDiscounts($data) as $appliedDiscount) {
            $this->getManager()->persist($appliedDiscount);
        }
    }

    /**
     * @return EntityManagerInterface
     */
    private function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry->getEntityManagerForClass(AppliedDiscount::class);
        }

        return $this->manager;
    }

    /**
     * @return AppliedDiscountRepository
     */
    private function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->getManager()->getRepository(AppliedDiscount::class);
        }

        return $this->repository;
    }
}
