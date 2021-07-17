<?php

namespace Oro\Bundle\ProductBundle\ContentWidget;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\ProductBundle\Form\Type\ProductSegmentContentWidgetSettingsType;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

/**
 * Type for the product segment widgets.
 */
class ProductSegmentContentWidgetType implements ContentWidgetTypeInterface
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var int */
    private $instanceNumber = 0;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /** {@inheritdoc} */
    public static function getName(): string
    {
        return 'product_segment';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.product.content_widget_type.product_segment.label';
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
                            $twig->render('@OroProduct/ProductSegmentContentWidget/options.html.twig', $data),
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
        return $formFactory->create(ProductSegmentContentWidgetSettingsType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        $data = $contentWidget->getSettings();
        $data['instanceNumber'] = $this->instanceNumber++;

        $segment = $data['segment'] ?? null;
        if ($segment) {
            $data['product_segment'] = $this->registry->getManagerForClass(Segment::class)
                ->getRepository(Segment::class)
                ->find($segment);
        } else {
            $data['product_segment'] = null;
        }

        unset($data['segment']);

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
