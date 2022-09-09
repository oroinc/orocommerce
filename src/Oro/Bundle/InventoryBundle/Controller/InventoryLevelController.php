<?php

namespace Oro\Bundle\InventoryBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTemplateTypeExtension;
use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTypeExtension;
use Oro\Bundle\InventoryBundle\Form\Handler\InventoryLevelHandler;
use Oro\Bundle\InventoryBundle\Form\Type\InventoryLevelGridType;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for InventoryLevel entity
 */
class InventoryLevelController extends AbstractController
{
    /**
     * @Route("/", name="oro_inventory_level_index")
     * @Template
     * @Acl(
     *      id="oro_inventory_level_view",
     *      type="entity",
     *      class="OroInventoryBundle:InventoryLevel",
     *      permission="VIEW"
     * )
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => InventoryLevel::class,
            'exportProcessors' => array_keys(InventoryLevelExportTypeExtension::getProcessorAliases()),
            'exportTemplateProcessors' => array_keys(
                InventoryLevelExportTemplateTypeExtension::getProcessorAliases()
            ),
        ];
    }

    /**
     * Edit product inventory levels
     *
     * @Route("/update/{id}", name="oro_inventory_level_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_product_inventory_update",
     *      type="entity",
     *      class="OroInventoryBundle:InventoryLevel",
     *      permission="EDIT"
     * )
     */
    public function updateAction(Product $product, Request $request): array|RedirectResponse
    {
        if (!$this->isGranted('EDIT', $product)) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(
            InventoryLevelGridType::class,
            null,
            ['product' => $product]
        );

        $handler = new InventoryLevelHandler(
            $this->getDoctrine()->getManagerForClass(InventoryLevel::class),
            $this->get(QuantityRoundingService::class),
            $this->get(InventoryManager::class)
        );

        $result = $this->get(UpdateHandlerFacade::class)->update(
            $product,
            $form,
            '',
            $request,
            $handler
        );

        if ($result instanceof Response) {
            return $result;
        }

        return array_merge($result, $this->widgetNoDataReasonsCheck($product));
    }

    private function widgetNoDataReasonsCheck(Product $product): array
    {
        $noDataReason = '';
        if (0 === count($product->getUnitPrecisions())) {
            $noDataReason = 'oro.inventory.inventorylevel.error.units';
        }

        return $noDataReason
            ? ['noDataReason' => $this->get(TranslatorInterface::class)->trans($noDataReason)]
            : [];
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                QuantityRoundingService::class,
                InventoryManager::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
