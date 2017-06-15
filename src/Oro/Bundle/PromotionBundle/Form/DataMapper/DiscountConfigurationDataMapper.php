<?php

namespace Oro\Bundle\PromotionBundle\Form\DataMapper;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Form\Type\BasicDiscountFormType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

class DiscountConfigurationDataMapper implements DataMapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        if (!$data instanceof DiscountConfiguration) {
            throw new UnexpectedTypeException($data, DiscountConfiguration::class);
        }

        /** @var FormInterface[]|\Traversable $forms */
        $forms = iterator_to_array($forms);
        $options = $data->getOptions();

        foreach ($forms as $form) {
            if ($form->getConfig()->getType()->getInnerType() instanceof PriceType
                && isset($options[AbstractDiscount::DISCOUNT_VALUE], $options[AbstractDiscount::DISCOUNT_CURRENCY])
            ) {
                $forms[AbstractDiscount::DISCOUNT_VALUE]->setData(
                    Price::create(
                        $options[AbstractDiscount::DISCOUNT_VALUE],
                        $options[AbstractDiscount::DISCOUNT_CURRENCY]
                    )
                );
                unset($options[AbstractDiscount::DISCOUNT_VALUE]);
                unset($options[AbstractDiscount::DISCOUNT_CURRENCY]);
            }

            foreach ($options as $name => $value) {
                if ($name === $form->getName()) {
                    $form->setData($value);
                }
            }
        }

        $forms[BasicDiscountFormType::DISCOUNT_FIELD]->setData($data->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        if (!$data instanceof DiscountConfiguration) {
            throw new UnexpectedTypeException($data, DiscountConfiguration::class);
        }

        /** @var FormInterface[]|\Traversable $forms */
        $forms = iterator_to_array($forms);

        $options = [];
        foreach ($forms as $form) {
            if ($form->getConfig()->getType()->getInnerType() instanceof PriceType) {
                $options[AbstractDiscount::DISCOUNT_VALUE] = $form->getData()->getValue();
                $options[AbstractDiscount::DISCOUNT_CURRENCY] = $form->getData()->getCurrency();
            } elseif (BasicDiscountFormType::DISCOUNT_FIELD !== $form->getName()) {
                $options[$form->getName()] = $form->getData();
            }
        }
        $data->setOptions($options);

        $data->setType($forms[BasicDiscountFormType::DISCOUNT_FIELD]->getData());
    }
}
