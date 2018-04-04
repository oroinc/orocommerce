<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Form\Type\BrandType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * CRUD controller for Brand entity
 */
class BrandController extends Controller
{
    /**
     * @Route("/", name="oro_product_brand_index")
     * @Template
     * @Acl(
     *      id="oro_product_brand_view",
     *      type="entity",
     *      class="OroProductBundle:Brand",
     *      permission="VIEW"
     * )
     * @return array
     */
    public function indexAction()
    {
        return [
            'gridName' => 'brand-grid'
        ];
    }

    /**
     * @Route("/create", name="oro_product_brand_create")
     * @Template("OroProductBundle:Brand:update.html.twig")
     * @Acl(
     *      id="oro_product_brand_create",
     *      type="entity",
     *      class="OroProductBundle:Brand",
     *      permission="CREATE"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new Brand(), $request);
    }

    /**
     * @Route("/update/{id}", name="oro_product_brand_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_product_brand_update",
     *      type="entity",
     *      class="OroProductBundle:Brand",
     *      permission="EDIT"
     * )
     * @param Brand   $brand
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Brand $brand, Request $request)
    {
        return $this->update($brand, $request);
    }

    /**
     * @param Brand   $brand
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Brand $brand, Request $request)
    {
        return $this->get('oro_form.update_handler')->update(
            $brand,
            $this->createForm(BrandType::NAME, $brand),
            $this->get('translator')->trans('oro.product.brand.form.update.messages.saved'),
            $request,
            null
        );
    }

    /**
     * @Route("/get-changed-urls/{id}", name="oro_product_brand_get_changed_slugs", requirements={"id"="\d+"})
     *
     * @AclAncestor("oro_product_brand_update")
     *
     * @param Brand $brand
     * @return JsonResponse
     */
    public function getChangedSlugsAction(Brand $brand)
    {
        return new JsonResponse($this->get('oro_redirect.helper.changed_slugs_helper')
            ->getChangedSlugsData($brand, BrandType::class));
    }
}
