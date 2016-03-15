<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\LayoutBundle\Provider\ImageTypeConfigProvider;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageCollectionValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_product_image_collection_validator';
    /**
     * @var ImageTypeConfigProvider
     */
    protected $imageTypeConfigProvider;

    /**
     * @param ImageTypeConfigProvider $imageTypeConfigProvider
     */
    public function __construct(ImageTypeConfigProvider $imageTypeConfigProvider)
    {
        $this->imageTypeConfigProvider = $imageTypeConfigProvider;
    }

    /**
     * @param ProductImage[] $value
     * @param Constraint|ProductImageCollection $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $maxNumbers = [];
        $imageTypeConfigs = $this->imageTypeConfigProvider->getConfigs();

        foreach ($imageTypeConfigs as $imageType => $config) {
            $maxNumbers[$imageType] = (int) $config['max_number'];
        }

        $countImageTypes = [];

        foreach ($value as $productImage) {
            $types = $productImage->getTypes();
            foreach ($imageTypeConfigs as $imageType => $config) {
                if (isset($types[$imageType]) && $types[$imageType]) {
                    isset($countImageTypes[$imageType])
                        ? $countImageTypes[$imageType]++
                        : $countImageTypes[$imageType] = 1;
                }
            }
        }

        foreach ($imageTypeConfigs as $imageType => $config) {
            if ($maxNumbers[$imageType] > 0 && $countImageTypes[$imageType] > $maxNumbers[$imageType]) {
                $this->context->addViolation(
                    $constraint->message,
                    [
                        '%type%' =>  $imageType,
                        '%maxNumber%' => $maxNumbers[$imageType]
                    ]
                );

            }
        }
    }
}
