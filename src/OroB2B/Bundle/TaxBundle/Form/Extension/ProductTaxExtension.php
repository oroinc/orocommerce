<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;

class ProductTaxExtension extends AbstractTypeExtension
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'taxCode',
                ProductTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.tax.producttaxcode.entity_label'
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

        $taxCode = $this->getProductTaxCode($product);

        $event->getForm()->get('taxCode')->setData($taxCode);
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

        $entityManager = $this->doctrineHelper->getEntityManager('OroB2BTaxBundle:ProductTaxCode');

        $taxCodeNew = $form->get('taxCode')->getData();
        $taxCode = $this->getProductTaxCode($product);

        if (!$taxCodeNew) {
            if ($taxCode) {
                $taxCode->removeProduct($product);
                $entityManager->flush();
            }
            return;
        }

        $taxCodeId = $taxCode ? $taxCode->getId() : 0;

        if ($taxCodeId != $taxCodeNew->getId()) {
            if ($taxCode) {
                $taxCode->removeProduct($product);
            }
            $taxCodeNew->addProduct($product);
            $entityManager->flush();
        }
    }

    /**
     * @param Product $product
     * @return ProductTaxCode|null
     */
    protected function getProductTaxCode($product)
    {
        /** @var ProductTaxCodeRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroB2BTaxBundle:ProductTaxCode');

        return $repository->findOneByProduct($product);
    }
}
