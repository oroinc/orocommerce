<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\RFPBundle\Form\Handler\RequestCreateQuoteHandler;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Form\Handler\RequestChangeStatusHandler;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestChangeStatusType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestType;

class RequestController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_rfp_request_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_request_view",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
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
            'formCreateQuote' => $this->getCreateQuoteForm($request)->createView()
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_rfp_request_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_rfp_request_view")
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
     * @Route("/", name="orob2b_rfp_request_index")
     * @Template
     * @AclAncestor("orob2b_rfp_request_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_rfp.entity.request.class')
        ];
    }

    /**
     * @Route("/update/{id}", name="orob2b_rfp_request_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="orob2b_rfp_request_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroB2BRFPBundle:Request"
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
     * @Route("/create_quote/{id}", name="orob2b_rfp_request_create_quote", requirements={"id"="\d+"})
     * @Template("OroB2BRFPBundle:Request:view.html.twig")
     * @Acl(
     *      id="orob2b_rfp_request_create_quote",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
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
     * @Route("/change_status/{id}", name="orob2b_rfp_request_change_status", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_request_update",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
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
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('orob2b_rfp.entity.request.class')
            ),
            $this->container->get('templating')
        );

        $formAction = $this->get('router')->generate('orob2b_rfp_request_change_status', [
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
                    'route'         => 'orob2b_rfp_request_update',
                    'parameters'    => ['id' => $request->getId()]
                ];
            },
            function (Request $request) {
                return [
                    'route'         => 'orob2b_rfp_request_view',
                    'parameters'    => ['id' => $request->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.rfp.controller.request.saved.message')
        );
    }

    /**
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function createQuote(Request $request)
    {
        $form = $this->getCreateQuoteForm($request);
        $handler = new RequestCreateQuoteHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BPricingBundle:PriceList'),
            $this->getUser()
        );

        return $this->get('oro_form.model.update_handler')
            ->handleUpdate(
                $request,
                $form,
                function (Request $request) {
                    return [
                        'route'         => 'orob2b_rfp_request_view',
                        'parameters'    => ['id' => $request->getId()]
                    ];
                },
                function () use ($handler) {
                    return [
                        'route'         => 'orob2b_sale_quote_update',
                        'parameters'    => ['id' => $handler->getQuote()->getId()]
                    ];
                },
                $this->getTranslator()->trans('orob2b.rfp.message.request.create_quote.success'),
                $handler,
                function (Request $entity, FormInterface $form) use ($handler) {
                    /* @var $session Session */
                    $session = $this->get('session');
                    $session->getFlashBag()->add(
                        'error',
                        $this->getTranslator()->trans('orob2b.rfp.message.request.create_quote.error')
                    );

                    if ($handler->getException()) {
                        $this->getLogger()->error($handler->getException()->getMessage());
                    }

                    return [
                        'entity' => $entity,
                        'formCreateQuote' => $form->createView(),
                    ];
                }
            );
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->get('logger');
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * @param Request $request
     * @return Form
     */
    protected function getCreateQuoteForm(Request $request = null)
    {
        return $this->createFormBuilder($request)->getForm();
    }
}
