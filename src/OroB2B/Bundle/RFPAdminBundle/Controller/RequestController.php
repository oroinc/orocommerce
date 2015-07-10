<?php

namespace OroB2B\Bundle\RFPAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\RFPAdminBundle\Form\Handler\RequestCreateQuoteHandler;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestCreateQuoteType;
use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Form\Handler\RequestChangeStatusHandler;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestChangeStatusType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestType;

class RequestController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_rfp_admin_request_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_admin_request_view",
     *      type="entity",
     *      class="OroB2BRFPAdminBundle:Request",
     *      permission="VIEW"
     * )
     *
     * @param Request $request
     * @return array
     */
    public function viewAction(Request $request)
    {
        return [
            'entity' => $request,
            'formCreateQuote' => $this->createForm(new RequestCreateQuoteType(), $request)->createView()
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_rfp_admin_request_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_rfp_admin_request_view")
     *
     * @param Request $request
     * @return array
     */
    public function infoAction(Request $request)
    {
        return [
            'entity' => $request
        ];
    }

    /**
     * @Route("/", name="orob2b_rfp_admin_request_index")
     * @Template
     * @AclAncestor("orob2b_rfp_admin_request_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_rfp_admin.request.class')
        ];
    }

    /**
     * @Route("/update/{id}", name="orob2b_rfp_admin_request_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="orob2b_rfp_admin_request_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroB2BRFPAdminBundle:Request"
     * )
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request)
    {
        return $this->update($request);
    }

    /**
     * @Route("/create_quote/{id}", name="orob2b_rfp_admin_request_create_quote", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_admin_request_create_quote",
     *      type="entity",
     *      class="OroB2BRFPAdminBundle:Request",
     *      permission="EDIT"
     * )
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function createQuoteAction(Request $request)
    {
        return $this->createQuote($request);
    }

    /**
     * @Route("/change_status/{id}", name="orob2b_rfp_admin_request_change_status", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_admin_request_update",
     *      type="entity",
     *      class="OroB2BRFPAdminBundle:Request",
     *      permission="EDIT"
     * )
     *
     * @param Request $request
     * @throws NotFoundHttpException
     * @return array
     */
    public function changeStatusAction(Request $request)
    {
        if (!$this->getRequest()->get('_widgetContainer')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(RequestChangeStatusType::NAME, ['status' => $request->getStatus()]);
        $handler = new RequestChangeStatusHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BRFPAdminBundle:Request'),
            $this->container->get('templating')
        );

        $formAction = $this->get('router')->generate('orob2b_rfp_admin_request_change_status', [
            'id' => $request->getId()
        ]);

        return [
            'entity'     => $request,
            'saved'      => $handler->process($request) ? true : false,
            'form'       => $form->createView(),
            'formAction' => $formAction
        ];
    }

    /**
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Request $request)
    {
        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');
        return $handler->handleUpdate(
            $request,
            $this->createForm(RequestType::NAME, $request),
            function (Request $request) {
                return [
                    'route' => 'orob2b_rfp_admin_request_update',
                    'parameters' => ['id' => $request->getId()]
                ];
            },
            function (Request $request) {
                return [
                    'route' => 'orob2b_rfp_admin_request_view',
                    'parameters' => ['id' => $request->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.rfpadmin.controller.request.saved.message')
        );
    }


    /**
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function createQuote(Request $request)
    {
        $form = $this->createForm(new RequestCreateQuoteType(), $request);
        $handler = new RequestCreateQuoteHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BPricingBundle:PriceList'),
            $this->getUser()
        );
        $quoteId = $handler->process($request);
        /** @var $flashBag FlashBag */
        $flashBag = $this->getRequest()->getSession()->getFlashBag();
        if ($quoteId) {
            $flashBag->add(
                'success',
                $this->get('translator')->trans('orob2b.rfpadmin.message.request.create_quote.success')
            );

            return $this->redirect($this->generateUrl('orob2b_sale_quote_update', ['id' => $quoteId]));
        } else {
            $flashBag->add(
                'error',
                $this->get('translator')->trans('orob2b.rfpadmin.message.request.create_quote.error')
            );

            return $this->redirect($this->generateUrl('orob2b_rfp_admin_request_view', ['id' => $request->getId()]));
        }
    }
}
