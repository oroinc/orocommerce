<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductFormExtension extends AbstractTypeExtension
{
    /**
     * @var QueryHintResolverInterface
     */
    protected $hintResolver;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     * @param QueryHintResolverInterface $hintResolver
     */
    public function __construct(ManagerRegistry $registry, QueryHintResolverInterface $hintResolver)
    {
        $this->registry = $registry;
        $this->hintResolver = $hintResolver;
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

        $prices = $this->getProductPriceRepository()->getPricesByProduct($this->hintResolver, $product);

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
        /** @var ProductPrice[] $prices */
        $prices = (array)$form->get('prices')->getData();
        foreach ($prices as $price) {
            $price->setProduct($product);
        }

        if (!$form->isValid()) {
            return;
        }

        $repository = $this->getProductPriceRepository();
        // persist existing prices
        $persistedPriceIds = [];
        foreach ($prices as $price) {
            $priceId = $price->getId();
            if ($priceId) {
                $persistedPriceIds[] = $priceId;
            }

            $price->setProduct($product);
            $repository->persist($this->hintResolver, $price);
        }

        // remove deleted prices
        if ($product->getId()) {
            $existingPrices = $repository->getPricesByProduct($this->hintResolver, $product);
            foreach ($existingPrices as $price) {
                if (!in_array($price->getId(), $persistedPriceIds, true)) {
                    $repository->remove($this->hintResolver, $price);
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
}
