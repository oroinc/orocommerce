<?php

namespace Oro\Bundle\ShippingBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ShippingMethodsConfigsRuleHandler
{
    const UPDATE_FLAG = 'update_methods_flag';

    /** @var RequestStack */
    protected $requestStack;

    /** @var EntityManager */
    protected $em;

    /** @var FormInterface */
    protected $form;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param RequestStack $requestStack
     * @param EntityManager $em
     */
    public function __construct(RequestStack $requestStack, EntityManager $em)
    {
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    /**
     * @param FormInterface $form
     * @param ShippingMethodsConfigsRule $entity
     * @return bool
     */
    public function process(FormInterface $form, ShippingMethodsConfigsRule $entity)
    {
        $form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $form->submit($request);
            if (!$request->get(self::UPDATE_FLAG, false) && $form->isValid()) {
                $this->em->persist($entity);
                $this->em->flush();

                return true;
            }
        }

        return false;
    }
}
