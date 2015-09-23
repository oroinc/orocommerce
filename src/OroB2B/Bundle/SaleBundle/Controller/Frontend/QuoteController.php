<?php

namespace OroB2B\Bundle\SaleBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\ObjectManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteToOrderType;
use OroB2B\Bundle\SaleBundle\Form\Handler\QuoteToOrderHandler;
use OroB2B\Bundle\SaleBundle\Model\QuoteToOrderConverter;

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
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
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
     * @Route("/create-order/{id}", name="orob2b_sale_frontend_quote_create_order", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_order_frontend_create")
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Quote $quote
     * @return RedirectResponse
     */
    public function createOrderAction(Quote $quote)
    {
        /** @var QuoteToOrderConverter $converter */
        $converter = $this->get('orob2b_sale.service.quote_to_order_converter');
        $order = $converter->convert($quote, $this->getUser());

        $objectManager = $this->getOrderObjectManager();
        $objectManager->persist($order);
        $objectManager->flush();

        $this->addSuccessfulConversionMessage();

        return $this->redirectToRoute('orob2b_order_frontend_view', ['id' => $order->getId()]);
    }

    /**
     * @Route(
     *      "/create-order/from-widget/{id}",
     *      name="orob2b_sale_frontend_quote_create_order_from_widget",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_order_frontend_create")
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     * @Template("OroB2BSaleBundle:Quote/Frontend:createOrder.html.twig")
     *
     * @param Request $request
     * @param Quote $quote
     * @return array|RedirectResponse
     */
    public function createOrderFromWidgetAction(Request $request, Quote $quote)
    {
        $form = $this->createForm(QuoteToOrderType::NAME, $quote);
        $objectManager = $this->getOrderObjectManager();
        $converter = $this->get('orob2b_sale.service.quote_to_order_converter');

        $handler = new QuoteToOrderHandler($form, $request, $objectManager, $converter, $this->getUser());
        $order = $handler->process($quote);

        if ($order) {
            $this->addSuccessfulConversionMessage();
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

    /**
     * @return ObjectManager
     */
    protected function getOrderObjectManager()
    {
        return $this->getDoctrine()->getManagerForClass(
            $this->getParameter('orob2b_order.entity.order.class')
        );
    }

    protected function addSuccessfulConversionMessage()
    {
        $this->get('session')->getFlashBag()->add(
            'success',
            $this->getTranslator()->trans('orob2b.frontend.sale.message.quote.create_order.success')
        );
    }
}
