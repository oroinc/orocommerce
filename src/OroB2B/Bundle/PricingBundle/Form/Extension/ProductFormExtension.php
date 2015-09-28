<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Product $product */
        $product = $builder->getData();

        $builder->add(
            'prices',
            ProductPriceCollectionType::NAME,
            [
                'label' => 'orob2b.pricing.productprice.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'constraints' => [new UniqueProductPrices()],
                'options' => [
                    'product' => $product,
                ],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        $entityManager = $this->registry->getManagerForClass('OroB2BPricingBundle:ProductPrice');

        // persist existing prices
        /** @var ProductPrice[] $prices */
        $prices = (array)$form->get('prices')->getData();
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
        return $this->registry->getManagerForClass('OroB2BPricingBundle:ProductPrice')
            ->getRepository('OroB2BPricingBundle:ProductPrice');
    }
}
