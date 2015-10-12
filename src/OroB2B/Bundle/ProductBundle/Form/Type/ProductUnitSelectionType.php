<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class ProductUnitSelectionType extends AbstractType
{
    const NAME = 'orob2b_product_unit_selection';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitFormatter;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param ProductUnitLabelFormatter $productUnitFormatter
     * @param TranslatorInterface $translator
     */
    public function __construct(ProductUnitLabelFormatter $productUnitFormatter, TranslatorInterface $translator)
    {
        $this->productUnitFormatter = $productUnitFormatter;
        $this->translator = $translator;
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'setAcceptableUnits']);
    }

    /**
     * @param FormEvent $event
     */
    public function setAcceptableUnits(FormEvent $event)
    {
        $form = $event->getForm();
        $options = $form->getConfig()->getOptions();

        if ($options['choices_updated']) {
            return;
        }

        $formParent = $form->getParent();
        if (!$formParent) {
            return;
        }

        /* @var $productUnitHolder ProductUnitHolderInterface */
        $productUnitHolder = $formParent->getData();
        if (!$productUnitHolder) {
            return;
        }

        $productHolder = $productUnitHolder->getProductHolder();
        if (!$productHolder) {
            return;
        }

        $product = $productHolder->getProduct();
        if (!$product) {
            return;
        }

        $options['choices'] = $this->getProductUnits($product);
        $options['choices_updated'] = true;

        $formParent
            ->add($form->getName(), $form->getConfig()->getType()->getName(), $options);
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getProductUnits(Product $product = null)
    {
        $choices = [];

        if ($product) {
            foreach ($product->getUnitPrecisions() as $unitPrecision) {
                $choices[] = $unitPrecision->getUnit();
            }
        }

        return $choices;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => $this->entityClass,
                'property' => 'code',
                'compact' => false,
                'choices_updated' => false,
                'required' => true,
                'empty_label' => 'orob2b.product.productunit.removed',
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $formParent = $form->getParent();
        if (!$formParent) {
            return;
        }

        /* @var $productUnitHolder ProductUnitHolderInterface */
        $productUnitHolder = $formParent->getData();
        if (!$productUnitHolder) {
            return;
        }

        $productHolder = $productUnitHolder->getProductHolder();
        if (!$productHolder || !$productHolder->getProduct()) {
            return;
        }

        $product = $productHolder->getProduct();
        $choices = $this->getProductUnits($product);

        $productUnit = $productUnitHolder->getProductUnit();

        if (!$productUnit || ($product && !in_array($productUnit, $choices, true))) {
            $emptyValueTitle = $this->translator->trans(
                $options['empty_label'],
                ['{title}' => $productUnitHolder->getProductUnitCode()]
            );
            $view->vars['choices'] = [new ChoiceView(null, null, $emptyValueTitle, ['selected' => true])];
        }

        $this->setChoicesViews($view, $choices, $options);
    }

    /**
     * @param FormView $view
     * @param array $choices
     * @param array $options
     */
    public function setChoicesViews(FormView $view, array $choices, array $options)
    {
        $choicesViews = [];

        $choices = $this->productUnitFormatter->formatChoices($choices, $options['compact']);
        foreach ($choices as $key => $value) {
            $choicesViews[] = new ChoiceView($value, $key, $value);
        }

        $view->vars['choices'] = $choicesViews;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
