<?php

namespace OroB2B\Bundle\ProductBundle\Controller;

use OroB2B\Bundle\ProductBundle\Duplicator\ProductDuplicator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_product_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_product_view",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="VIEW"
     * )
     *
     * @param Product $product
     * @return array
     */
    public function viewAction(Product $product)
    {
        return [
            'entity' => $product
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_product_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_product_view")
     *
     * @param Product $product
     * @return array
     */
    public function infoAction(Product $product)
    {
        return [
            'product' => $product
        ];
    }

    /**
     * @Route("/", name="orob2b_product_index")
     * @Template
     * @AclAncestor("orob2b_product_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_product.product.class')
        ];
    }

    /**
     * Create product form
     *
     * @Route("/create", name="orob2b_product_create")
     * @Template("OroB2BProductBundle:Product:update.html.twig")
     * @Acl(
     *      id="orob2b_product_create",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new Product());
    }

    /**
     * Edit product form
     *
     * @Route("/update/{id}", name="orob2b_product_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_product_update",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="EDIT"
     * )
     * @param Product $product
     * @return array|RedirectResponse
     */
    public function updateAction(Product $product)
    {
        return $this->update($product);
    }

    /**
     * Duplicate product
     *
     * @Route("/duplicate/{id}", name="orob2b_product_duplicate", requirements={"id"="\d+"})
     * @Acl(
     *      id="orob2b_product_duplicate",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="CREATE"
     * )
     * @param Product $product
     * @return RedirectResponse
     */
    public function duplicateAction(Product $product)
    {

        /**
         * @TODO move to services.yml
         */
        $duplicator = new ProductDuplicator();
        $duplicator->setObjectManager($this->get('doctrine.orm.entity_manager'));
        $duplicator->setEventDispatcher($this->get('event_dispatcher'));

        $productCopy = $duplicator->duplicate($product);

        return $this->redirect(
            $this->generateUrl(
                'orob2b_product_view',
                ['id' => $productCopy->getId()]
            )
        );
    }

    /**
     * @param Product $product
     * @return array|RedirectResponse
     */
    protected function update(Product $product)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $product,
            $this->get('orob2b_product.form.product'),
            function (Product $product) {
                return array(
                    'route' => 'orob2b_product_update',
                    'parameters' => array('id' => $product->getId())
                );
            },
            function (Product $product) {
                return array(
                    'route' => 'orob2b_product_view',
                    'parameters' => array('id' => $product->getId())
                );
            },
            $this->get('translator')->trans('orob2b.product.controller.product.saved.message')
        );
    }
}
