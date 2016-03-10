<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageCollectionValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_product_image_collection_validator';
    /**
     * @var Theme
     */
    private $theme;

    /**
     * @param ThemeManager $themeManager
     * @param RequestStack $requestStack
     */
    public function __construct(ThemeManager $themeManager, RequestStack $requestStack)
    {
        $this->theme = $themeManager->getTheme($requestStack->getCurrentRequest()->attributes->get('_theme'));
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

        foreach ($this->getImageTypes() as $imageType => $config) {
            $maxNumbers[$imageType] = $config['max_number'] ?: 99999 ;
        }

        $countImageTypes = [];

        foreach ($value as $productImage) {
            $types = $productImage->getTypes();
            foreach ($this->getImageTypes() as $imageType => $config) {
                if (isset($types[$imageType]) && $types[$imageType]) {
                    isset($countImageTypes[$imageType])
                        ? $countImageTypes[$imageType]++
                        : $countImageTypes[$imageType] = 1;
                }
            }
        }

        foreach ($this->getImageTypes() as $imageType => $config) {
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

    /**
     * @return string[]
     */
    private function getImageTypes()
    {
        return $this->theme->getDataByKey('images')['types'];
    }
}
