<?php

namespace Oro\Bundle\PromotionBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CouponDataConverter extends ConfigurableTableDataConverter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param FieldHelper $fieldHelper
     * @param RelationCalculatorInterface $relationCalculator
     * @param TranslatorInterface $translator
     */
    public function __construct(
        FieldHelper $fieldHelper,
        RelationCalculatorInterface $relationCalculator,
        TranslatorInterface $translator
    ) {
        parent::__construct($fieldHelper, $relationCalculator);

        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertHeaderToFrontend(array $backendHeader)
    {
        $headers = parent::convertHeaderToFrontend($backendHeader);
        if (array_key_exists('promotion:rule:name', $headers)) {
            $headers['promotion:rule:name'] = $this->translator->trans('oro.promotion.coupon.importexport.promotion');
        }
        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertHeaderToBackend(array $frontendHeader)
    {
        $headers = parent::convertHeaderToBackend($frontendHeader);
        $promotionHeader = $this->translator->trans('oro.promotion.coupon.importexport.promotion');
        if (array_key_exists($promotionHeader, $headers)) {
            $headers[$promotionHeader] = 'promotion:rule:name';
        }
        return $headers;
    }
}
