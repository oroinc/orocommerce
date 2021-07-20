<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Form type for the settings of Product Mini-Block content widget.
 */
class ProductMiniBlockContentWidgetSettingsType extends AbstractType
{
    /** @var ManagerRegistry */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $manager = $this->registry->getManagerForClass(Product::class);

        $builder->add(
            'product',
            ProductSelectType::class,
            [
                'label' => 'oro.product.entity_label',
                'required' => true,
                'create_enabled' => false,
                'block' => 'options',
                'block_config' => [
                    'options' => [
                        'title' => 'oro.product.sections.options'
                    ]
                ],
                'constraints' => [
                    new NotBlank(),
                ],
                'transformer' => new CallbackTransformer(
                    static function ($data) use ($manager) {
                        if (!is_object($data)) {
                            return $data;
                        }

                        $transformer = new EntityToIdTransformer($manager, Product::class);

                        return $transformer->transform($data);
                    },
                    static function ($data) {
                        return $data;
                    }
                )
            ]
        );

        $builder->add(
            'show_prices',
            CheckboxType::class,
            [
                'label' => 'oro.product.content_widget_type.product_mini_block.options.show_prices.label',
                'required' => false,
                'block' => 'options',
                'constraints' => [
                    new Type('boolean'),
                ],
            ]
        );

        $builder->add(
            'show_add_button',
            CheckboxType::class,
            [
                'label' => 'oro.product.content_widget_type.product_mini_block.options.show_add_button.label',
                'required' => false,
                'block' => 'options',
                'constraints' => [
                    new Type('boolean'),
                ],
            ]
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event) use ($manager) {
                $data = $event->getData();

                $productId = $data['product'] ?? null;
                if ($productId) {
                    $data['product'] = $manager->getRepository(Product::class)
                        ->find($data['product']);
                }

                if (!is_array($data) || !array_key_exists('show_add_button', $data)) {
                    $data['show_add_button'] = true;
                }

                if (!is_array($data) || !array_key_exists('show_prices', $data)) {
                    $data['show_prices'] = true;
                }

                $event->setData($data);
            }
        );
    }
}
