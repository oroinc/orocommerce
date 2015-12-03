<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Oro\DBAL\Types\MoneyType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;

class ProductPriceValueType extends AbstractType
{
    const NAME = 'orob2b_pricing_product_price_value';

    /** @var RoundingServiceInterface */
    protected $roundingService;

    /**
     * @param RoundingServiceInterface $roundingService
     */
    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $formValue = $event->getData();
                $roundedValue = $this->roundingService->round($formValue, MoneyType::TYPE_SCALE);

                if ($formValue != $roundedValue) {
                    $event->setData((string)$roundedValue);
                }
            }
        );
    }

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('constraints', [new NotBlank(), new Range(['min' => 0]), new Decimal()]);
    }

    /** {@inheritdoc} */
    public function getParent()
    {
        return 'text'; // @todo: https://magecore.atlassian.net/browse/BB-1621 should be number
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }
}
