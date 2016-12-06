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
     * @var string|null
     */
    protected $labelPDF;

    /**
     * @var string|null
     */
    protected $parcelNumber;

    /**
     * @var string|null
     */
    protected $yourInternalId;

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
            //TODO: we are using the first item on the LabelDataList.
            // Could this list have more than one item? Not clear on the API doc
            $labelData = reset($labelResponse[self::DPD_LABEL_DATA_LIST_KEY]);
            if (!array_key_exists(self::DPD_LABEL_DATA_PARCEL_NUMBER_KEY, $labelData)) {
                throw new \InvalidArgumentException('No ParcelNo parameter found in LabelData');
            }
            $this->parcelNumber = $labelData[self::DPD_LABEL_DATA_PARCEL_NUMBER_KEY];

            if (!array_key_exists(self::DPD_LABEL_DATA_YOUR_INTERNAL_ID_KEY, $labelData)) {
                throw new \InvalidArgumentException('No YourInternalID parameter found in LabelData');
            }
            $this->yourInternalId = $labelData[self::DPD_LABEL_DATA_YOUR_INTERNAL_ID_KEY];
        }
    }

    /**
     * @return string|null
     */
    public function getLabelPDF() {
        return $this->labelPDF;
    }

    /**
     * @return string|null
     */
    public function getParcelNumber() {
        return $this->parcelNumber;
    }

    /**
     * @return string|null
     */
    public function getYourInternalId()
    {
        return $this->yourInternalId;
    }
}