<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks if image group contains at least one image for any size
 */
class HasAtLeastOneSizeImageValidator extends ConstraintValidator
{
    private const LABELS = [
        'extraLargeImage' => [
            'oro.cms.imageslide.extra_large_image.label',
            'oro.cms.imageslide.extra_large_image2x.label',
            'oro.cms.imageslide.extra_large_image3x.label',
        ],
        'largeImage' => [
            'oro.cms.imageslide.large_image.label',
            'oro.cms.imageslide.large_image2x.label',
            'oro.cms.imageslide.large_image3x.label',
        ],
        'mediumImage' => [
            'oro.cms.imageslide.medium_image.label',
            'oro.cms.imageslide.medium_image2x.label',
            'oro.cms.imageslide.medium_image3x.label',
        ],
        'smallImage' => [
            'oro.cms.imageslide.small_image.label',
            'oro.cms.imageslide.small_image2x.label',
            'oro.cms.imageslide.small_image3x.label',
        ],
    ];

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof HasAtLeastOneSizeImage) {
            throw new UnexpectedTypeException($constraint, HasAtLeastOneSizeImage::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof ImageSlide) {
            throw new UnexpectedValueException($value, ImageSlide::class);
        }

        $groups = [
            'extraLargeImage',
            'largeImage',
            'mediumImage',
            'smallImage',
        ];

        $sizes = [
            '',
            '2x',
            '3x',
        ];

        foreach ($groups as $group) {
            $isGroupValid = false;
            foreach ($sizes as $size) {
                $field = sprintf('%s%s', ucfirst($group), $size);
                $getter = sprintf('get%s', $field);
                /** @var File $data */
                $data = call_user_func_array([$value, $getter], []);
                if ($data instanceof File && !$data->isEmptyFile()) {
                    $isGroupValid = true;
                    break;
                }
            }

            if ($isGroupValid !== true) {
                $this->context
                    ->buildViolation('oro.cms.image_slider.image.has_at_least_one_size_image.message')
                    ->setParameter(
                        '{{ fields }}',
                        implode(
                            ', ',
                            array_map(fn ($label) => $this->translator->trans($label), self::LABELS[$group])
                        )
                    )
                    ->setCode(NotBlank::IS_BLANK_ERROR)
                    ->atPath($group)
                    ->addViolation();
            }
        }
    }
}
