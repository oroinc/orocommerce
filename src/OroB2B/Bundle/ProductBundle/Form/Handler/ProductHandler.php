<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductHandler
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
     * @param Product $product
     * @return bool True on successful processing, false otherwise
     */
    public function process(Product $product)
    {
        $this->form->setData($product);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->manager->persist($product);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
