<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Represents {@see ProductKitItem::$kitItemProducts} collection.
 */
class ProductKitItemProductsType extends AbstractType
{
    private TranslatorInterface $translator;

    /** @var \Closure(): DataTransformerInterface  */
    private \Closure $viewDataTransformer;

    /** @var \Closure(): DataTransformerInterface  */
    private \Closure $modelDataTransformer;

    public function __construct(
        TranslatorInterface $translator,
        \Closure $viewDataTransformer,
        \Closure $modelDataTransformer
    ) {
        $this->translator = $translator;
        $this->viewDataTransformer = $viewDataTransformer;
        $this->modelDataTransformer = $modelDataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this->getViewDataTransformer());
        $builder->addModelTransformer($this->getModelDataTransformer());

        // Kit item products collection may contain uninitialized proxy product entities. That probably means that they
        // were not found. This listener adds proper form errors for such products and removes them from the collection.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) {
            $form = $event->getForm();
            if (!$form->isSynchronized() || !$form->getData()) {
                return;
            }

            foreach ($form->getData() as $key => $kitItemProduct) {
                $product = $kitItemProduct->getProduct();
                if ($product instanceof Proxy && !$product->__isInitialized()) {
                    try {
                        $product->__load();
                    } catch (EntityNotFoundException $exception) {
                        $message = $this->translator->trans(
                            'oro.product.productkititemproduct.product.not_found',
                            ['%product_id%' => $product->getId()],
                            'validators'
                        );
                        $form->addError(new FormError($message, null, [], null, $exception));

                        // Removes invalid element from collection.
                        $form->getData()->remove($key);
                    }
                }
            }
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['kitItemId'] = (int)$options['kit_item']?->getId();
        $view->vars['selectedProductsIds'] = array_map(
            static fn (ProductKitItemProduct $kitItemProduct) => $kitItemProduct->getProduct()->getId(),
            (array)$form->getData()?->toArray()
        );
        $view->vars['sortOrderConstraints'] = [
            'Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal' => new Decimal(),
            'Range' => new Range(['min' => 0])
        ];
        $view->vars['attr']['data-type'] = 'json-collection';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'kit_item' => null,
            'invalid_message' => 'oro.product.productkititem.kititemproducts.invalid_message',
            'error_bubbling' => false,
        ]);
        $resolver->setAllowedTypes('kit_item', [ProductKitItem::class, 'null']);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_kit_item_products';
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    private function getViewDataTransformer(): DataTransformerInterface
    {
        return ($this->viewDataTransformer)();
    }

    private function getModelDataTransformer(): DataTransformerInterface
    {
        return ($this->modelDataTransformer)();
    }
}
