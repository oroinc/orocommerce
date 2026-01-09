<?php

namespace Oro\Bundle\PaymentBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles form submission and persistence for payment methods configuration rules.
 *
 * This handler processes POST and PUT requests for payment method configuration rules,
 * validates the form data, and persists the rule entity to the database when valid.
 */
class PaymentMethodsConfigsRuleHandler
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
     * @param PaymentMethodsConfigsRule $entity
     * @return bool
     */
    public function process(FormInterface $form, PaymentMethodsConfigsRule $entity)
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
