<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

/**
 * Loads TEXT_ALIGNMENT enum values for the ImageSlide.
 */
class LoadImageSlideTextAlignments extends AbstractEnumFixture
{
    /** @var array */
    private static $data = [
        ImageSlide::TEXT_ALIGNMENT_CENTER => 'Center',
        ImageSlide::TEXT_ALIGNMENT_LEFT => 'Left',
        ImageSlide::TEXT_ALIGNMENT_RIGHT => 'Right',
        ImageSlide::TEXT_ALIGNMENT_TOP_LEFT => 'Top-Left',
        ImageSlide::TEXT_ALIGNMENT_TOP_CENTER => 'Top-Center',
        ImageSlide::TEXT_ALIGNMENT_TOP_RIGHT => 'Top-Right',
        ImageSlide::TEXT_ALIGNMENT_BOTTOM_LEFT => 'Bottom-Left',
        ImageSlide::TEXT_ALIGNMENT_BUTTOM_CENTER => 'Buttom-Center',
        ImageSlide::TEXT_ALIGNMENT_BOTTOM_RIGHT => 'Bottom-Right',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return self::$data;
    }

    /**
     * @return array
     */
    public static function getDataKeys()
    {
        return array_keys(self::$data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return ImageSlide::TEXT_ALIGNMENT_CODE;
    }
}
