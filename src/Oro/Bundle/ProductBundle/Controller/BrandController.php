<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Form\Type\BrandType;
use Oro\Bundle\RedirectBundle\Helper\ChangedSlugsHelper;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for Brand entity
 */
class BrandController extends AbstractController
{
    /**
     * @return array
     */
    #[Route(path: '/', name: 'oro_product_brand_index')]
    #[Template]
    #[Acl(id: 'oro_product_brand_view', type: 'entity', class: Brand::class, permission: 'VIEW')]
    public function indexAction()
    {
        return [
            'gridName' => 'brand-grid'
        ];
    }

    /**
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/create', name: 'oro_product_brand_create')]
    #[Template('@OroProduct/Brand/update.html.twig')]
    #[Acl(id: 'oro_product_brand_create', type: 'entity', class: Brand::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update(new Brand(), $request);
    }

    /**
     * @param Brand   $brand
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_product_brand_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_product_brand_update', type: 'entity', class: Brand::class, permission: 'EDIT')]
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
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $brand,
            $this->createForm(BrandType::class, $brand),
            $this->container->get(TranslatorInterface::class)->trans('oro.product.brand.form.update.messages.saved'),
            $request,
            null
        );
    }

    /**
     *
     *
     * @param Brand $brand
     * @return JsonResponse
     */
    #[Route(
        path: '/get-changed-urls/{id}',
        name: 'oro_product_brand_get_changed_slugs',
        requirements: ['id' => '\d+']
    )]
    #[AclAncestor('oro_product_brand_update')]
    public function getChangedSlugsAction(Brand $brand)
    {
        return new JsonResponse($this->container->get(ChangedSlugsHelper::class)
            ->getChangedSlugsData($brand, BrandType::class));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            ChangedSlugsHelper::class,
            UpdateHandlerFacade::class,
            TranslatorInterface::class,
        ]);
    }
}
