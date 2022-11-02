<?php

namespace Oro\Bundle\ProductBundle\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\ProductBundle\Form\Type\ProductMiniBlockContentWidgetSettingsType;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

/**
 * Type for the product mini-block widgets.
 */
class ProductMiniBlockContentWidgetType implements ContentWidgetTypeInterface
{
    private const PRODUCT_LIST_TYPE = 'product_mini_block';

    private ProductListBuilder $productListBuilder;
    private int $instanceNumber = 0;

    public function __construct(ProductListBuilder $productListBuilder)
    {
        $this->productListBuilder = $productListBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'product_mini_block';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.product.content_widget_type.product_mini_block.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        $data = $this->getWidgetData($contentWidget);

        return [
            [
                'title' => 'oro.product.sections.options',
                'subblocks' => [
                    [
                        'data' => [
                            $twig->render('@OroProduct/ProductMiniBlockContentWidget/options.html.twig', $data)
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface
    {
        return $formFactory->create(ProductMiniBlockContentWidgetSettingsType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        $data = $contentWidget->getSettings();
        $data['instanceNumber'] = $this->instanceNumber++;

        $productId = $data['product'] ?? null;
        if ($productId) {
            $data['product'] = $this->getProduct($productId);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isInline(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }

    private function getProduct(int $productId): ?ProductView
    {
        $result = null;
        $products = $this->productListBuilder->getProductsByIds(self::PRODUCT_LIST_TYPE, [$productId]);
        foreach ($products as $product) {
            if ($product->getId() === $productId) {
                $result = $product;
                break;
            }
        }

        return $result;
    }
}
