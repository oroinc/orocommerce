<?php

namespace Oro\Bundle\ShippingBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles form submission for shipping methods configuration rules.
 *
 * This handler processes the shipping rule form, validates the submitted data, and persists
 * the {@see ShippingMethodsConfigsRule} entity to the database. It supports an update flag
 * to allow form updates without persistence for dynamic form modifications.
 */
class ShippingMethodsConfigsRuleHandler
{
    use RequestHandlerTrait;

    public const UPDATE_FLAG = 'update_methods_flag';

    /** @var RequestStack */
    protected $requestStack;

    /** @var EntityManager */
    protected $em;

    /** @var FormInterface */
    protected $form;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

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
            $this->submitPostPutRequest($form, $request);
            if (!$request->get(self::UPDATE_FLAG, false) && $form->isValid()) {
                $this->em->persist($entity);
                $this->em->flush();

                return true;
            }
        }

        return false;
    }
}
