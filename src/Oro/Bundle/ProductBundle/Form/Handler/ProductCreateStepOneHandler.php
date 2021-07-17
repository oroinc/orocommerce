<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductCreateStepOneHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    public function __construct(
        FormInterface $form,
        Request $request
    ) {
        $this->form = $form;
        $this->request = $request;
    }

    /**
     * @return bool
     */
    public function process()
    {
        if ($this->request->isMethod(Request::METHOD_POST)) {
            $this->form->handleRequest($this->request);

            if ($this->form->isSubmitted() && $this->form->isValid()) {
                return true;
            }
        }

        return false;
    }
}
