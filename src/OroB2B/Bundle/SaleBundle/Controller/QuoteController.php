<?php

namespace OroB2B\Bundle\SaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Psr\Log\LoggerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Form\Handler\CreateQuoteFromRfpHandler;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;

class QuoteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_sale_quote_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_sale_quote_view",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="VIEW"
     * )
     *
     * @param Quote $quote
     * @return array
     */
    public function viewAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }

    /**
     * @Route("/", name="orob2b_sale_quote_index")
     * @Template
     * @AclAncestor("orob2b_sale_quote_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_sale.entity.quote.class')
        ];
    }

    /**
     * @Route("/create", name="orob2b_sale_quote_create")
     * @Template("OroB2BSaleBundle:Quote:update.html.twig")
     * @Acl(
     *     id="orob2b_sale_quote_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroB2BSaleBundle:Quote"
     * )
     */
    public function createAction()
    {
        return $this->update(new Quote());
    }

    /**
     * @Route("/create_from_rfp_form/{id}", name="orob2b_sale_quote_createfromrfpform", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote:block/createFromRfpForm.html.twig")
     *
     * @AclAncestor("orob2b_sale_quote_create")
     *
     * @param RFPRequest $rfpRequest
     * @return array
     */
    public function createFromRfpFormAction(RFPRequest $rfpRequest)
    {
        return [
            'entity' => $rfpRequest,
            'formCreateQuote' => $this->getCreateFromRfpForm($rfpRequest)->createView(),
        ];
    }

    /**
     * @Route("/create_from_rfp/{id}", name="orob2b_sale_quote_createfromrfp", requirements={"id"="\d+"})
     * @Template("OroB2BRFPBundle:Request:view.html.twig")
     *
     * @AclAncestor("orob2b_sale_quote_create")
     *
     * @param Request $request
     * @param RFPRequest $rfpRequest
     * @return array
     */
    public function createFromRfpAction(Request $request, RFPRequest $rfpRequest)
    {
        return $this->createFromRfp($request, $rfpRequest);
    }

    /**
     * @Route("/update/{id}", name="orob2b_sale_quote_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="orob2b_sale_quote_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroB2BSaleBundle:Quote"
     * )
     *
     * @param Quote $quote
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Quote $quote)
    {
        return $this->update($quote);
    }

    /**
     * @Route("/info/{id}", name="orob2b_sale_quote_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_sale_quote_view")
     *
     * @param Quote $quote
     * @return array
     */
    public function infoAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }

    /**
     * @param Quote $quote
     * @return array|RedirectResponse
     */
    protected function update(Quote $quote)
    {
        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');
        return $handler->handleUpdate(
            $quote,
            $this->createForm(QuoteType::NAME, $quote),
            function (Quote $quote) {
                return [
                    'route'         => 'orob2b_sale_quote_update',
                    'parameters'    => ['id' => $quote->getId()]
                ];
            },
            function (Quote $quote) {
                return [
                    'route'         => 'orob2b_sale_quote_view',
                    'parameters'    => ['id' => $quote->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.sale.controller.quote.saved.message')
        );
    }

    /**
     * @param Request $request
     * @param RFPRequest $rfpRequest
     * @return array|RedirectResponse
     */
    protected function createFromRfp(Request $request, RFPRequest $rfpRequest)
    {
        $form = $this->getCreateFromRfpForm($rfpRequest);
        $handler = new CreateQuoteFromRfpHandler(
            $form,
            $request,
            $this->getDoctrine()->getManagerForClass('OroB2BSaleBundle:Quote'),
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
                $this->getTranslator()->trans('orob2b.sale.message.quote.create_from_rfp.success'),
                $handler,
                function (RFPRequest $entity, FormInterface $form) use ($handler) {
                    /* @var $session Session */
                    $session = $this->get('session');
                    $session->getFlashBag()->add(
                        'error',
                        $this->getTranslator()->trans('orob2b.sale.message.quote.create_from_rfp.error')
                    );

                    if ($handler->getException()) {
                        $this->getLogger()->error($handler->getException());
                    }

                    return [
                        'entity' => $entity,
                        'formCreateQuote' => $form->createView(),
                    ];
                }
            )
        ;
    }

    /**
     * @param RFPRequest $rfpRequest
     * @return Form
     */
    protected function getCreateFromRfpForm(RFPRequest $rfpRequest = null)
    {
        return $this->createFormBuilder($rfpRequest)->getForm();
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
}
