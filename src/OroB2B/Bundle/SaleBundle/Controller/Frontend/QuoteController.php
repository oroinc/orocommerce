<?php

namespace OroB2B\Bundle\SaleBundle\Controller\Frontend;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteToOrderType;
use OroB2B\Bundle\SaleBundle\Form\Handler\QuoteToOrderHandler;

class QuoteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_sale_quote_frontend_view", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote/Frontend:view.html.twig")
     * @Acl(
     *      id="orob2b_sale_quote_frontend_view",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     *
     * @param Quote $quote
     * @return array
     */
    public function viewAction(Quote $quote)
    {
        return [
            'entity' => $quote,
        ];
    }

    /**
     * @Route("/", name="orob2b_sale_quote_frontend_index")
     * @Template("OroB2BSaleBundle:Quote/Frontend:index.html.twig")
     * @Acl(
     *      id="orob2b_sale_quote_frontend_index",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
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
     * @Route("/info/{id}", name="orob2b_sale_quote_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote/Frontend/widget:info.html.twig")
     * @AclAncestor("orob2b_sale_quote_frontend_view")
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
     * @Route("/create_order/{id}", name="orob2b_sale_frontend_quote_create_order", requirements={"id"="\d+"})
     * @Acl(
     *      id="orob2b_sale_frontend_quote_create_order",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @Template("OroB2BSaleBundle:Quote/Frontend:view.html.twig")
     *
     * @param Request $request
     * @param Quote $quote
     * @return array|RedirectResponse
     */
    public function createOrderAction(Request $request, Quote $quote)
    {
        return $this->createOrder($request, $quote);
    }

    /**
     * @Route(
     *      "/create-order/from-widget/{id}",
     *      name="orob2b_sale_frontend_quote_create_order_from_widget",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_sale_frontend_quote_create_order")
     * @Template("OroB2BSaleBundle:Quote/Frontend:createOrder.html.twig")
     *
     * @param Request $request
     * @param Quote $quote
     * @return array|RedirectResponse
     */
    public function createOrderFromWidgetAction(Request $request, Quote $quote)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = $this->getDoctrine()->getManagerForClass(
            $this->getParameter('orob2b_sale.entity.quote.class')
        );
        $converter = $this->get('orob2b_sale.service.quote_to_order_converter');

        $form = $this->createForm(QuoteToOrderType::NAME, $quote);
        $handler = new QuoteToOrderHandler($form, $request, $objectManager, $converter, $this->getUser());
        $order = $handler->process($quote);

        if ($order) {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->getTranslator()->trans('orob2b.frontend.sale.message.quote.create_order.success')
            );
        }

        return [
            'form' => $form->createView(),
            'quote' => $quote,
            'order' => $order,
        ];
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }
}
