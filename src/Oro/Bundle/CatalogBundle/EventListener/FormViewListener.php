<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds category information to the product view and edit pages.
 */
class FormViewListener
{
    private const CATEGORY_FIELD = 'category';
    private const GENERAL_BLOCK = 'general';

    public function __construct(
        private TranslatorInterface $translator,
        private DoctrineHelper $doctrineHelper,
        private AuthorizationCheckerInterface $authorizationChecker,
        private FieldAclHelper $fieldAclHelper
    ) {
    }

    public function onProductView(BeforeListRenderEvent $event): void
    {
        if (!$this->authorizationChecker->isGranted('oro_catalog_category_view')) {
            return;
        }

        $product = $event->getEntity();

        if (!$product instanceof Product) {
            throw new UnexpectedTypeException($product, Product::class);
        }

        /** @var CategoryRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Category::class);
        $category = $repository->findOneByProduct($product);

        if (!$category) {
            return;
        }

        if (!$this->fieldAclHelper->isFieldViewGranted($product, 'category')) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroCatalog/Product/category_view.html.twig',
            ['entity' => $category]
        );

        $this->prependCategoryBlock($event->getScrollData(), $template);
    }

    public function onProductEdit(BeforeListRenderEvent $event): void
    {
        if (!$this->authorizationChecker->isGranted('oro_catalog_category_view')) {
            return;
        }

        $product = $event->getEntity();
        if ($product && !$this->fieldAclHelper->isFieldAvailable($product, 'category')) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroCatalog/Product/category_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addCategoryBlock($event->getScrollData(), $template);
    }

    protected function addCategoryBlock(ScrollData $scrollData, string $html): void
    {
        $blockLabel = $this->translator->trans('oro.catalog.product.section.catalog');
        $blockId = $scrollData->addBlock($blockLabel, 300);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }

    private function prependCategoryBlock(ScrollData $scrollData, string $template): void
    {
        $data = $scrollData->getData();

        if (!empty($data[ScrollData::DATA_BLOCKS][self::GENERAL_BLOCK][ScrollData::SUB_BLOCKS][0][ScrollData::DATA])) {
            /** @var array $subData */
            $subData = $data[ScrollData::DATA_BLOCKS][self::GENERAL_BLOCK][ScrollData::SUB_BLOCKS][0][ScrollData::DATA];

            // No any sort support of fields order
            // insert as first element
            $subData = [self::CATEGORY_FIELD => $template] + $subData;

            $data[ScrollData::DATA_BLOCKS][self::GENERAL_BLOCK][ScrollData::SUB_BLOCKS][0][ScrollData::DATA] = $subData;
            $scrollData->setData($data);
        }
    }
}
