<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Psr\Log\LoggerInterface;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\RFPBundle\Form\Handler\RequestCreateQuoteHandler;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
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
     * @param RFPRequest $rfpRequest
     * @return array
     */
    public function viewAction(RFPRequest $rfpRequest)
    {
        return [
            'entity' => $rfpRequest,
            'formCreateQuote' => $this->getCreateQuoteForm($rfpRequest)->createView(),
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_rfp_request_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_rfp_request_view")
     *
     * @param RFPRequest $rfpRequest
     * @return array
     */
    public function infoAction(RFPRequest $rfpRequest)
    {
        return [
            'entity' => $rfpRequest,
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
            'entity_class' => $this->container->getParameter('orob2b_rfp.entity.request.class'),
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
     * @param RFPRequest $rfpRequest
     *
     * @return array|RedirectResponse
     */
    public function updateAction(RFPRequest $rfpRequest)
    {
        return $this->update($rfpRequest);
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
     * @param RFPRequest $rfpRequest
     * @param Request $request
     * @return RedirectResponse
     */
    public function createQuoteAction(RFPRequest $rfpRequest, Request $request)
    {
        return $this->createQuote($rfpRequest, $request);
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
     * @param RFPRequest $rfpRequest
     * @param Request $request
     * @throws NotFoundHttpException
     * @return array
     */
    public function changeStatusAction(RFPRequest $rfpRequest, Request $request)
    {
        if (!$request->get('_widgetContainer')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(RequestChangeStatusType::NAME, ['status' => $rfpRequest->getStatus()]);
        $handler = new RequestChangeStatusHandler(
            $form,
            $request,
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('orob2b_rfp.entity.request.class')
            ),
            $this->container->get('templating')
        );

        $formAction = $this->get('router')->generate(
            'orob2b_rfp_request_change_status',
            [
                'id' => $rfpRequest->getId(),
            ]
        );

        return [
            'entity' => $rfpRequest,
            'saved' => $handler->process($rfpRequest),
            'form' => $form->createView(),
            'formAction' => $formAction,
        ];
    }

    /**
     * @param RFPRequest $rfpRequest
     * @return array|RedirectResponse
     */
    protected function update(RFPRequest $rfpRequest)
    {
        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');

        return $handler->handleUpdate(
            $rfpRequest,
            $this->createForm(RequestType::NAME, $rfpRequest),
            function (RFPRequest $request) {
                return [
                    'route' => 'orob2b_rfp_request_update',
                    'parameters' => ['id' => $request->getId()],
                ];
            },
            function (RFPRequest $request) {
                return [
                    'route' => 'orob2b_rfp_request_view',
                    'parameters' => ['id' => $request->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.rfp.controller.request.saved.message')
        );
    }

    /**
     * @param RFPRequest $rfpRequest
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function createQuote(RFPRequest $rfpRequest, Request $request)
    {
        $form = $this->getCreateQuoteForm($rfpRequest);
        $handler = new RequestCreateQuoteHandler(
            $form,
            $request,
            $this->getDoctrine()->getManagerForClass('OroB2BPricingBundle:PriceList'),
            $this->getUser()
        );

        return $this->get('oro_form.model.update_handler')
            ->handleUpdate(
                $rfpRequest,
                $form,
                function (RFPRequest $request) {
                    return [
                        'route' => 'orob2b_rfp_request_view',
                        'parameters' => ['id' => $request->getId()],
                    ];
                },
                function () use ($handler) {
                    return [
                        'route' => 'orob2b_sale_quote_update',
                        'parameters' => ['id' => $handler->getQuote()->getId()],
                    ];
                },
                $this->getTranslator()->trans('orob2b.rfp.message.request.create_quote.success'),
                $handler,
                function (RFPRequest $entity, FormInterface $form) use ($handler) {
                    /* @var $session Session */
                    $session = $this->get('session');
                    $session->getFlashBag()->add(
                        'error',
                        $this->getTranslator()->trans('orob2b.rfp.message.request.create_quote.error')
                    );

                    if ($handler->getException()) {
                        $this->getLogger()->error($handler->getException());
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
     * @param RFPRequest $rfpRequest
     * @return Form
     */
    protected function getCreateQuoteForm(RFPRequest $rfpRequest = null)
    {
        return $this->createFormBuilder($rfpRequest)->getForm();
    }
}
