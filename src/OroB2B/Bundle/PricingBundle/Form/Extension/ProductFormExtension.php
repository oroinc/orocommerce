<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
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
     * @var RoundingService
     */
    protected $roundingService;

    /**
     * @param ManagerRegistry $registry
     * @param RoundingService $roundingService
     */
    public function __construct(ManagerRegistry $registry, RoundingService $roundingService)
    {
        $this->registry = $registry;
        $this->roundingService = $roundingService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'prices',
            ProductPriceCollectionType::NAME,
            [
                'label' => 'orob2b.pricing.productprice.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'constraints' => [new UniqueProductPrices()]
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (!isset($data['unitPrecisions'], $data['prices'])) {
            return;
        }

        $unitPrecisions = [];
        foreach ($data['unitPrecisions'] as $unitPrecision) {
            $unitPrecisions[$unitPrecision['unit']] = $unitPrecision['precision'];
        }

        foreach ($data['prices'] as &$price) {
            if (array_key_exists($price['unit'], $unitPrecisions)) {
                $price['quantity'] = $this->roundingService
                    ->round($price['quantity'], $unitPrecisions[$price['unit']]);
            }
        }

        $event->setData($data);
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
        $prices = $form->get('prices')->getData();
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
                if (!in_array($price->getId(), $persistedPriceIds)) {
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
