<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage as ProductImageEntity;

class ProductImageCollectionValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_product_image_collection_validator';

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @param ImageTypeProvider $imageTypeProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(ImageTypeProvider $imageTypeProvider, TranslatorInterface $translator)
    {
        $this->imageTypeProvider = $imageTypeProvider;
        $this->translator = $translator;
    }

    /**
     * @param ProductImageEntity[]|Collection $value
     * @param Constraint|ProductImageCollection $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $maxNumberByType = $this->getMaxNumberByType();
        $imagesByTypeCounter = $this->countImagesByType($value);

        foreach ($this->imageTypeProvider->getImageTypes() as $imageType) {
            $imageTypeName = $imageType->getName();

            if ($maxNumberByType[$imageTypeName] > 0 &&
                isset($imagesByTypeCounter[$imageTypeName]) &&
                $imagesByTypeCounter[$imageTypeName] > $maxNumberByType[$imageTypeName]
            ) {
                $this->context
                    ->buildViolation($constraint->message, [
                        '%type%' => $this->translator->trans($imageType->getLabel()),
                        '%maxNumber%' => $maxNumberByType[$imageTypeName]
                    ])
                    ->addViolation();
            }
        }
    }

    /**
     * @param ProductImageEntity[]|Collection $productImages
     * @return array
     */
    private function countImagesByType(Collection $productImages)
    {
        $imagesByTypeCounter = [];

        foreach ($productImages as $productImage) {
            $types = $productImage->getTypes();

            foreach ($types as $type) {
                if (isset($imagesByTypeCounter[$type])) {
                    $imagesByTypeCounter[$type]++;
                } else {
                    $imagesByTypeCounter[$type] = 1;
                }
            }
        }

        return $imagesByTypeCounter;
    }

    /**
     * @return array
     */
    private function getMaxNumberByType()
    {
        $maxNumbers = [];

        foreach ($this->imageTypeProvider->getImageTypes() as $imageType) {
            $maxNumbers[$imageType->getName()] = $imageType->getMaxNumber();
        }

        return $maxNumbers;
    }
}
