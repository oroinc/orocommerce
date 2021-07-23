<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to find out the translation key of a "related items" relation label:
 *   - get_related_items_translation_key
 */
class RelatedItemExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'get_related_items_translation_key',
                [$this, 'getRelatedItemsTranslationKey']
            ),
        ];
    }

    /**
     * @return string
     */
    public function getRelatedItemsTranslationKey()
    {
        return $this->container->get('oro_product.related_item.helper.config_helper')
            ->getRelatedItemsTranslationKey();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_product.related_item.helper.config_helper' => RelatedItemConfigHelper::class,
        ];
    }
}
