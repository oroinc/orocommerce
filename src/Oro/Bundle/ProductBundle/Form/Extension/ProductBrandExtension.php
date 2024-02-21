<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Form extension for adding brand field to a product form if current user granted to view and assign brand.
 */
class ProductBrandExtension extends AbstractTypeExtension
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $callback = function (FormEvent $event) {
            $product = $event->getForm()->getData();
            if (!$product instanceof Product) {
                return;
            }

            if (!$this->authorizationChecker->isGranted('oro_product_brand_view') ||
                !$this->authorizationChecker->isGranted('VIEW', $product->getBrand())
            ) {
                $event->getForm()->remove('brand');
            }
        };

        # Since attributes are added dynamic in several stages, need to remove them at each stage.
        # See Oro\Bundle\EntityConfigBundle\Form\Extension\DynamicAttributesExtension::onPreSetData.
        $builder->addEventListener(FormEvents::POST_SET_DATA, $callback);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $callback);
    }
}
