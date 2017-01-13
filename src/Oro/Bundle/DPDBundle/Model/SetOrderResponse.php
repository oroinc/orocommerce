<?php

namespace Oro\Bundle\DPDBundle\Model;

class SetOrderResponse extends DPDResponse
{
    const DPD_LABEL_RESPONSE_KEY = 'LabelResponse';
    const DPD_LABEL_PDF_KEY = 'LabelPDF';
    const DPD_LABEL_DATA_LIST_KEY = 'LabelDataList';
    const DPD_LABEL_DATA_YOUR_INTERNAL_ID_KEY = 'YourInternalID';
    const DPD_LABEL_DATA_PARCEL_NUMBER_KEY = 'ParcelNo';

    /**
     * @var string
     */
    protected $labelPDF;

    /**
     * @var array
     */
    protected $parcelNumbers = array();

    /**
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);
        if ($this->isSuccessful()) {
            if (!$this->values->offsetExists(self::DPD_LABEL_RESPONSE_KEY)) {
                throw new \InvalidArgumentException('No LabelResponse parameter found in response data');
            }
            $labelResponse = $this->values->offsetGet(self::DPD_LABEL_RESPONSE_KEY);

            if (!array_key_exists(self::DPD_LABEL_PDF_KEY, $labelResponse)) {
                throw new \InvalidArgumentException('No LabelPDF parameter found in response data');
            }
            $this->labelPDF = $labelResponse[self::DPD_LABEL_PDF_KEY];

            if (!array_key_exists(self::DPD_LABEL_DATA_LIST_KEY, $labelResponse)) {
                throw new \InvalidArgumentException('No LabelDataList parameter found in response data');
            }
            $labelDataList = $labelResponse[self::DPD_LABEL_DATA_LIST_KEY];

            foreach ($labelDataList as $labelData) {
                if (!array_key_exists(self::DPD_LABEL_DATA_PARCEL_NUMBER_KEY, $labelData)) {
                    throw new \InvalidArgumentException('No ParcelNo parameter found in LabelData');
                }
                $parcelNumber = $labelData[self::DPD_LABEL_DATA_PARCEL_NUMBER_KEY];
                if (!array_key_exists(self::DPD_LABEL_DATA_YOUR_INTERNAL_ID_KEY, $labelData)) {
                    throw new \InvalidArgumentException('No YourInternalID parameter found in LabelData');
                }
                $yourInternalId = $labelData[self::DPD_LABEL_DATA_YOUR_INTERNAL_ID_KEY];
                $this->parcelNumbers[$yourInternalId] = $parcelNumber;
            }


        }
    }

    /**
     * @param bool $decode
     * @return string
     */
    public function getLabelPDF($decode = true) {
        return $decode?base64_decode($this->labelPDF):$this->labelPDF;
    }

    /**
     * @return array
     */
    public function getParcelNumbers() {
        return $this->parcelNumbers;
    }
}