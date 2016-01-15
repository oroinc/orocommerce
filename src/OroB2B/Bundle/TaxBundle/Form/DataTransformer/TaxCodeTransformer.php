<?php

namespace OroB2B\Bundle\TaxBundle\Form\DataTransformer;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode;

class TaxCodeTransformer implements DataTransformerInterface
{
    /** @var RegistryInterface */
    protected $doctrine;

    /** @var string */
    protected $taxCodeClassName;

    /**
     * TaxCodeConfigConverter constructor.
     * @param RegistryInterface $doctrine
     * @param string $taxCodeClassName
     */
    public function __construct(RegistryInterface $doctrine, $taxCodeClassName)
    {
        $this->doctrine = $doctrine;
        $this->taxCodeClassName = $taxCodeClassName;
    }

    /**
     * {@inheritdoc}
     * @param AbstractTaxCode[]|array $taxCodes
     */
    public function transform($taxCodes)
    {
        if (empty($taxCodes)) {
            return [];
        }

        $result = [];
        foreach ($taxCodes as $taxCode) {
            $result[] = $taxCode->getId();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * @param array $ids
     */
    public function reverseTransform($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $taxCodes = $this->doctrine->getRepository($this->taxCodeClassName)
            ->findBy(['id' => $ids] ?: []);

        usort(
            $taxCodes,
            function ($a, $b) {
                /** @var AbstractTaxCode $a */
                /** @var AbstractTaxCode $b */
                return ($a->getCode() < $b->getCode()) ? -1 : 1;
            }
        );

        return $taxCodes;
    }
}
