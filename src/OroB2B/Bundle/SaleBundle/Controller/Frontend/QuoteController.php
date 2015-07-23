<?php

namespace OroB2B\Bundle\SaleBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroB2B\Bundle\SaleBundle\Entity\Quote;

class QuoteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_sale_frontend_quote_view", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote/Frontend:view.html.twig")
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
     * @Route("/", name="orob2b_sale_frontend_quote_index")
     * @Template("OroB2BSaleBundle:Quote/Frontend:index.html.twig")
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_sale.entity.quote.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_sale_frontend_quote_info", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote/Frontend/widget:info.html.twig")
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
}
