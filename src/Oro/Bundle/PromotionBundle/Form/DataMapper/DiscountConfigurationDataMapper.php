<?php

namespace Oro\Bundle\PromotionBundle\Form\DataMapper;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
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
        if (null === $data) {
            return;
        }

        if (!is_array($data)) {
            throw new UnexpectedTypeException($data, 'array');
        }

        /** @var FormInterface[]|\Traversable $forms */
        $forms = iterator_to_array($forms);
        $this->setDataFromOptions($forms, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        if (null === $data) {
            return;
        }

        if (!is_array($data)) {
            throw new UnexpectedTypeException($data, 'array');
        }

        /** @var FormInterface[]|\Traversable $forms */
        $forms = iterator_to_array($forms);

        $data = $this->getOptionsFromData($forms);
    }

    /**
     * @param FormInterface[] $forms
     * @param array $options
     */
    private function setDataFromOptions(array &$forms, $options)
    {
        foreach ($forms as $form) {
            if ($form->getConfig()->getName() === DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD
                && isset($options[AbstractDiscount::DISCOUNT_VALUE], $options[AbstractDiscount::DISCOUNT_CURRENCY])
            ) {
                $forms[DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD]->setData(
                    MultiCurrency::create(
                        $options[AbstractDiscount::DISCOUNT_VALUE],
                        $options[AbstractDiscount::DISCOUNT_CURRENCY]
                    )
                );
            } elseif ($form->getConfig()->getName() === DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD
                && isset($options[AbstractDiscount::DISCOUNT_VALUE], $options[AbstractDiscount::DISCOUNT_TYPE])
                && DiscountInterface::TYPE_PERCENT === $options[AbstractDiscount::DISCOUNT_TYPE]
            ) {
                $forms[DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD]
                    ->setData($options[AbstractDiscount::DISCOUNT_VALUE]);
            }

            foreach ($options as $name => $value) {
                if ($name === $form->getName()) {
                    $form->setData($value);
                }
            }
        }
    }

    /**
     * @param FormInterface[] $forms
     *
     * @return array
     */
    private function getOptionsFromData(array $forms)
    {
        $options = [];

        foreach ($forms as $form) {
            if ($form->getConfig()->getName() === DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD
                && $this->isAmountDiscountData($forms)
                && $form->getData() instanceof MultiCurrency
            ) {
                $options[AbstractDiscount::DISCOUNT_VALUE] = $form->getData()->getValue();
                $options[AbstractDiscount::DISCOUNT_CURRENCY] = $form->getData()->getCurrency();
            } elseif ($form->getConfig()->getName() === DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD
                && $this->isPercentDiscountData($forms)
            ) {
                $options[AbstractDiscount::DISCOUNT_VALUE] = $form->getData();
                unset($options[AbstractDiscount::DISCOUNT_CURRENCY]);
            } elseif (DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD !== $form->getName()
                && DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD !== $form->getName()
            ) {
                $options[$form->getName()] = $form->getData();
            }
        }

        return $options;
    }

    /**
     * @param FormInterface[] $forms
     *
     * @return bool
     */
    private function isAmountDiscountData($forms)
    {
        return isset($forms[AbstractDiscount::DISCOUNT_TYPE])
            && DiscountInterface::TYPE_AMOUNT === $forms[AbstractDiscount::DISCOUNT_TYPE]->getData();
    }

    /**
     * @param FormInterface[] $forms
     *
     * @return bool
     */
    private function isPercentDiscountData($forms)
    {
        return isset($forms[AbstractDiscount::DISCOUNT_TYPE])
            && DiscountInterface::TYPE_PERCENT === $forms[AbstractDiscount::DISCOUNT_TYPE]->getData();
    }
}
