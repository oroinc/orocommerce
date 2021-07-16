<?php

namespace Oro\Bundle\ProductBundle\ContentWidget;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductMiniBlockContentWidgetSettingsType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * Type for the product mini-block widgets.
 */
class ProductMiniBlockContentWidgetType implements ContentWidgetTypeInterface
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var int */
    private $instanceNumber = 0;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**@var ConfigManager */
    private $configManager;

    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->authorizationChecker = $authorizationChecker;
        $this->configManager = $configManager;
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
        $data['instanceNumber'] = $this->instanceNumber++;

        $product = $data['product'] ?? null;
        if ($product) {
            $data['product'] = $this->registry->getManagerForClass(Product::class)
                ->getRepository(Product::class)
                ->find($product);

            if (!$this->authorizationChecker->isGranted('VIEW', $data['product']) ||
                !$this->isInventoryStatusVisible($data['product'])
            ) {
                $data['product'] = null;
            }
        }

        return $data;
    }

    private function isInventoryStatusVisible(Product $product): bool
    {
        $allowedStatuses = $this->configManager->get('oro_product.general_frontend_product_visibility');

        return $product->getInventoryStatus() ?
            \in_array($product->getInventoryStatus()->getId(), $allowedStatuses, true) :
            true;
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
