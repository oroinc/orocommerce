<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductFormExtension extends AbstractTypeExtension
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Product $product */
        $product = $builder->getData();

        $builder->add(
            'prices',
            ProductPriceCollectionType::NAME,
            [
                'label' => 'oro.pricing.productprice.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'constraints' => [new UniqueProductPrices()],
                'options' => [
                    'product' => $product,
                ],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], 10);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product || !$product->getId()) {
            return;
        }

        $prices = $this->getProductPriceRepository()->getPricesByProduct($product);

        $event->getForm()->get('prices')->setData($prices);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var Product|null $product */
        $product = $form->getData();
        if (!$product) {
            return;
        }

        $submittedData = $event->getData();

        if (array_key_exists('prices', $submittedData)) {
            $submittedPrices = $submittedData['prices'];

            if ($product->getId()) {
                $replacedPrices = [];
                $existingPrices = $this->getProductPriceRepository()->getPricesByProduct($product);
                foreach ($submittedPrices as $key => $submittedPrice) {
                    foreach ($existingPrices as $k => $existingPrice) {
                        if ($key !== $k && $this->assertUniqueAttributes($submittedPrice, $existingPrice)) {
                            $replacedPrices[$k] = $submittedPrice;
                            break;
                        }
                    }
                }
                $correctPrices = array_replace($submittedPrices, $replacedPrices);
                $submittedData['prices'] = $correctPrices;
                $event->setData($submittedData);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product) {
            return;
        }

        $form = $event->getForm();
        /** @var ProductPrice[] $prices */
        $prices = (array)$form->get('prices')->getData();
        foreach ($prices as $price) {
            $price->setProduct($product);
        }

        if (!$form->isValid()) {
            return;
        }

        $entityManager = $this->registry->getManagerForClass('OroPricingBundle:ProductPrice');

        // persist existing prices
        $persistedPriceIds = [];
        foreach ($prices as $price) {
            $priceId = $price->getId();
            if ($priceId) {
                $persistedPriceIds[] = $priceId;
            }

            $price->setProduct($product);
            $entityManager->persist($price);
        }

        // remove deleted prices
        if ($product->getId()) {
            $existingPrices = $this->getProductPriceRepository()->getPricesByProduct($product);
            foreach ($existingPrices as $price) {
                if (!in_array($price->getId(), $persistedPriceIds, true)) {
                    $entityManager->remove($price);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->registry->getManagerForClass('OroPricingBundle:ProductPrice')
            ->getRepository('OroPricingBundle:ProductPrice');
    }

    /**
     * @param array        $submitted
     * @param ProductPrice $existing
     *
     * @return boolean
     */
    protected function assertUniqueAttributes(array $submitted, ProductPrice $existing)
    {
        if ($submitted['priceList'] !== (string)$existing->getPriceList()->getId()) {
            return false;
        }
        if ($submitted['price']['currency'] !== $existing->getPrice()->getCurrency()) {
            return false;
        }
        if ($submitted['quantity'] !== (string)$existing->getQuantity()) {
            return false;
        }
        if ($submitted['unit'] !== $existing->getUnit()->getCode()) {
            return false;
        }

        return true;
    }
}
