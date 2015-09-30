<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

class ProductUnitRemovedSelectionType extends AbstractType
{
    const NAME = 'orob2b_product_unit_removed_selection';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitFormatter;

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @param ProductUnitLabelFormatter $productUnitFormatter
     * @param TranslatorInterface $translator
     */
    public function __construct(ProductUnitLabelFormatter $productUnitFormatter, TranslatorInterface $translator)
    {
        $this->productUnitFormatter = $productUnitFormatter;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => $this->entityClass,
            'property' => 'code',
            'compact' => false,
            'required' => true,
            'empty_label' => 'orob2b.product.productunit.removed',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ProductUnitSelectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;
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

        $product = $productUnitHolder->getProductHolder()->getProduct();

        $choices = $this->getProductUnits($product);

        $productUnit = $productUnitHolder->getProductUnit();
        if (!$productUnit || ($product && !in_array($productUnit, $choices, true))) {
            $emptyValueTitle = $this->translator->trans($this->options['empty_label'], [
                '{title}' => $productUnitHolder->getProductUnitCode(),
            ]);
            $view->vars['empty_value'] =  $emptyValueTitle;
        }
        $choices = $this->productUnitFormatter->formatChoices($choices, $options['compact']);
        $choicesViews = [];
        foreach ($choices as $key => $value) {
            $choicesViews[] = new ChoiceView($value, $key, $value);
        }

        $view->vars['choices'] = $choicesViews;
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
}
