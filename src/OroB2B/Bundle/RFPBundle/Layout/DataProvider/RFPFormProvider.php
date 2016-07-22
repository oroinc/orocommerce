<?php

namespace OroB2B\Bundle\RFPBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestType;

class RFPFormProvider
{
    /** @var FormAccessor[] */
    protected $data = [];

    /** @var FormInterface[] */
    protected $form = [];

    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $request = $context->data()->get('entity');
        $requestId = $request->getId();

        if (!isset($this->data[$requestId])) {
            if ($requestId) {
                $action = FormAction::createByRoute('orob2b_rfp_frontend_request_update', ['id' => $requestId]);
            } else {
                $action = FormAction::createByRoute('orob2b_rfp_frontend_request_create');
            }
            $this->data[$requestId] = new FormAccessor(
                $this->getForm($request),
                $action
            );
        }

        return $this->data[$requestId];
    }

    /**
     * @param Request $request
     *
     * @return FormInterface
     */
    public function getForm(Request $request)
    {
        $requestId = $request->getId();

        if (!isset($this->form[$requestId])) {
            $this->form[$requestId] = $this->formFactory
                ->create(RequestType::NAME, $request);
        }

        return $this->form[$requestId];
    }
}
