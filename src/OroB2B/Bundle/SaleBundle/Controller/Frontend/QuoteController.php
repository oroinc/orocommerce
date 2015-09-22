<?php

namespace OroB2B\Bundle\SaleBundle\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
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
     *
     * @param Quote $quote
     * @return RedirectResponse
     */
    public function createOrderAction(Quote $quote)
    {
        /** @var QuoteToOrderConverter $converter */
        $converter = $this->get('orob2b_sale.service.quote_to_order_converter');
        $order = $converter->convert($quote);

        $em = $this->getDoctrine()->getManagerForClass(ClassUtils::getClass($order));
        $em->persist($order);
        $em->flush();

        return $this->redirectToRoute('orob2b_order_frontend_view', ['id' => $order->getId()]);
    }
}
