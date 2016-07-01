<?php

namespace OroB2B\Bundle\SaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;
use OroB2B\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use OroB2B\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;

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
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $quote = new Quote();
        $quote->setWebsite($this->get('orob2b_website.manager')->getCurrentWebsite());

        if (!$request->get(ProductDataStorage::STORAGE_KEY, false)) {
            return $this->update($quote, $request);
        }

        $this->createForm(QuoteType::NAME, $quote);

        $quoteClass = $this->container->getParameter('orob2b_sale.entity.quote.class');
        $em = $this->get('doctrine')->getManagerForClass($quoteClass);

        $em->persist($quote);
        $em->flush();

        return $this->redirectToRoute('orob2b_sale_quote_update', ['id' => $quote->getId()]);
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
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Quote $quote
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Quote $quote, Request $request)
    {
        return $this->update($quote, $request);
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
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Quote $quote, Request $request)
    {
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $quote->setAccount($this->getQuoteHandler()->getAccount());
            $quote->setAccountUser($this->getQuoteHandler()->getAccountUser());
        }

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
            $this->get('translator')->trans('orob2b.sale.controller.quote.saved.message'),
            null,
            function (Quote $quote, FormInterface $form, Request $request) {
                return [
                    'form' => $form->createView(),
                    'tierPrices' => $this->getQuoteProductPriceProvider()->getTierPrices($quote),
                    'matchedPrices' => $this->getQuoteProductPriceProvider()->getMatchedPrices($quote),
                    'isShippingAddressGranted' => $this->getQuoteAddressSecurityProvider()
                        ->isAddressGranted($quote, AddressType::TYPE_SHIPPING),
                ];
            }
        );
    }

    /**
     * @return QuoteProductPriceProvider
     */
    protected function getQuoteProductPriceProvider()
    {
        return $this->get('orob2b_sale.provider.quote_product_price');
    }

    /**
     * @return QuoteAddressSecurityProvider
     */
    protected function getQuoteAddressSecurityProvider()
    {
        return $this->get('orob2b_sale.provider.quote_address_security');
    }

    /**
     * @return \OroB2B\Bundle\SaleBundle\Model\QuoteRequestHandler
     */
    protected function getQuoteHandler()
    {
        return $this->get('orob2b_sale.service.quote_request_handler');
    }
}
