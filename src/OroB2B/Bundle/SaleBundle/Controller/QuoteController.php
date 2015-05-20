<?php

namespace OroB2B\Bundle\SaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroB2B\Bundle\SaleBundle\Entity\Quote;

class QuoteController extends Controller
{
    /**
     * @Route("/", name="orob2b_sale_quote_index")
     * @Template()
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_sale.quote.class')
        ];
    }
    
    /**
     * @Route("/create", name="orob2b_sale_quote_create")
     * @Template("OroB2BSaleBundle:Quote:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Quote());
    }

    /**
     * @Route("/update/{id}", name="orob2b_sale_quote_update")
     * @Template
     */
    public function updateAction(Quote $quote)
    {
        return $this->update($quote);
    }
    
    /**
     * @Route("/view/{id}", name="orob2b_sale_quote_view", requirements={"id"="\d+"})
     * @Template
     */
    public function viewAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }
    
    /**
     * @Route("/info/{id}", name="orob2b_sale_quote_info", requirements={"id"="\d+"})
     * @Template
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
        /* @var $handler \Oro\Bundle\FormBundle\Model\UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');
        
        return $handler->handleUpdate(
            $quote,
            $this->get('orob2b_sale.form.quote'),
            function (Quote $quote) {
                return array(
                    'route' => 'orob2b_sale_quote_update',
                    'parameters' => array('id' => $quote->getId())
                );
            },
            function (Quote $quote) {
                return array(
                    'route' => 'orob2b_sale_quote_view',
                    'parameters' => array('id' => $quote->getId())
                );
            },
            $this->get('translator')->trans('orob2b.sale.controller.quote.saved.message'),
            $this->get('orob2b_sale.form.handler.quote')
        );
    }
}
