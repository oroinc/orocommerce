<?php

namespace Oro\Bundle\ProductBundle\ContentWidget;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductMiniBlockContentWidgetSettingsType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

/**
 * Type for the product mini-block widgets.
 */
class ProductMiniBlockContentWidgetType implements ContentWidgetTypeInterface
{
    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /** {@inheritdoc} */
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
                            $twig->render('@OroProduct/ProductMiniBlockContentWidget/options.html.twig', $data),
                        ]
                    ],
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

        $product = $data['product'] ?? null;
        if ($product) {
            $data['product'] = $this->registry->getManagerForClass(Product::class)
                ->getRepository(Product::class)
                ->find($product);
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
}
