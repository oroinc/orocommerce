<?php

namespace Oro\Bundle\SaleBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Storage\ReturnRouteDataStorage;

class QuoteController extends Controller
{
    const REDIRECT_BACK_FLAG = 'redirect_back';
    /**
     * @Route("/view/{id}", name="oro_sale_quote_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_sale_quote_view",
     *      type="entity",
     *      class="OroSaleBundle:Quote",
     *      permission="VIEW"
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
     * @Route("/", name="oro_sale_quote_index")
     * @Template
     * @AclAncestor("oro_sale_quote_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_sale.entity.quote.class')
        ];
    }

    /**
     * @Route("/create", name="oro_sale_quote_create")
     * @Template("OroSaleBundle:Quote:update.html.twig")
     * @Acl(
     *     id="oro_sale_quote_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroSaleBundle:Quote"
     * )
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $quote = new Quote();

        if ($request->get(self::REDIRECT_BACK_FLAG, false)) {
            return $this->handleRequestAndRedirectBack(
                $quote,
                'OroSaleBundle:Quote:createWithReturn.html.twig'
            );
        }

        if (!$request->get(ProductDataStorage::STORAGE_KEY, false)) {
            return $this->update($quote);
        }

        $quote->setWebsite($this->get('oro_website.manager')->getDefaultWebsite());

        $this->createForm(QuoteType::NAME, $quote);

        $quoteClass = $this->container->getParameter('oro_sale.entity.quote.class');
        $em = $this->get('doctrine')->getManagerForClass($quoteClass);

        $em->persist($quote);
        $em->flush();

        return $this->redirectToRoute('oro_sale_quote_update', ['id' => $quote->getId()]);
    }

    /**
     * @Route("/update/{id}", name="oro_sale_quote_update", requirements={"id"="\d+"})
     * @Template("OroSaleBundle:Quote:update.html.twig")
     * @Acl(
     *     id="oro_sale_quote_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroSaleBundle:Quote"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
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
     * @Route("/info/{id}", name="oro_sale_quote_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_sale_quote_view")
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
        return $handler->update(
            $quote,
            $this->createForm(QuoteType::NAME, $quote),
            $this->get('translator')->trans('oro.sale.controller.quote.saved.message'),
            null,
            'quote_update'
        );
    }


    /**
     * @return \Oro\Bundle\SaleBundle\Model\QuoteRequestHandler
     */
    protected function getQuoteHandler()
    {
        return $this->get('oro_sale.service.quote_request_handler');
    }


    /**
     * Handles request which requires get back after Quote creating
     *
     * @param Quote $quote
     * @param string $template
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    private function handleRequestAndRedirectBack(Quote $quote, $template)
    {
        // Handle form validate and fetch pre-response
        $updateResponse = $this->update($quote);

        /** @var ReturnRouteDataStorage $redirectStorage */
        $redirectStorage = $this->get('oro_sale.storage.return_route_storage');
        $routeToRedirectBack = $redirectStorage->get();

        if ($this->isRequestHandledSuccessfully($updateResponse)) {
            // We don't need storage data anymore, so clean it and return user to route which we have to
            $redirectStorage->remove();
            return $this->redirectToRoute($routeToRedirectBack['route'], $routeToRedirectBack['parameters']);
        } else {
            // Render form with limited number of actions because we will redirect back
            return $this->render(
                $template,
                array_merge($updateResponse, [
                    'return_route' => $routeToRedirectBack
                ])
            );
        }
    }

    /**
     * Returns if request is checked by handler
     *
     * @param $updateResponse
     * @return bool
     */
    private function isRequestHandledSuccessfully($updateResponse)
    {
        return $updateResponse instanceof RedirectResponse;
    }
}
