<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;

class AttributeHandler
{
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
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * Process form
     *
     * @param Attribute $attribute
     * @return bool True on successful processing, false otherwise
     */
    public function process(Attribute $attribute)
    {
        $this->form->setData($attribute);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->manager->persist($attribute);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
