<?php

namespace OroB2B\Bundle\WarehouseBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
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
     * Process form
     *
     * @param Warehouse $entity
     * @return bool True on successful processing, false otherwise
     */
    public function process(Warehouse $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $this->manager->persist($entity);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
