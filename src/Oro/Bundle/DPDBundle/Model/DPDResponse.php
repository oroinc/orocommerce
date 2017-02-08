<?php

namespace Oro\Bundle\DPDBundle\Model;

class DPDResponse
{
    const DPD_ACK_KEY = 'Ack';
    const DPD_TIMESTAMP_KEY = 'TimeStamp';
    const DPD_ERROR_DATA_LIST_KEY = 'ErrorDataList';
    const DPD_ERROR_DATA_ID_KEY = 'ErrorID';
    const DPD_ERROR_DATA_CODE_KEY = 'ErrorCode';
    const DPD_ERROR_DATA_MSG_SHORT_KEY = 'ErrorMsgShort';
    const DPD_ERROR_DATA_MSG_LONG_KEY = 'ErrorMsgLong';

    /**
     * @var bool
     */
    protected $ack = false;

    /**
     * @var string
     */
    protected $timeStamp;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param array $data
     *
     * @throws \InvalidArgumentException
     */
    public function parse(array $data)
    {
        if (!array_key_exists(self::DPD_ACK_KEY, $data)) {
            throw new \InvalidArgumentException('No Ack parameter found in response data');
        }
        $this->ack = $data[self::DPD_ACK_KEY];

        if (!array_key_exists(self::DPD_TIMESTAMP_KEY, $data)) {
            throw new \InvalidArgumentException('No TimeStamp parameter found in response data');
        }
        $this->timeStamp = $data[self::DPD_TIMESTAMP_KEY];

        if (array_key_exists(self::DPD_ERROR_DATA_LIST_KEY, $data) && $data[self::DPD_ERROR_DATA_LIST_KEY]) {
            $this->errors = $data[self::DPD_ERROR_DATA_LIST_KEY];
        }
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->ack;
    }

    /**
     * @return string
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function getErrorMessagesLong()
    {
        $errMsgs = [];
        foreach ($this->getErrors() as $error) {
            $errMsgs[] =
                $error[self::DPD_ERROR_DATA_MSG_LONG_KEY].' (ErrorID='.$error[self::DPD_ERROR_DATA_ID_KEY].')';
        }

        return $errMsgs;
    }

    public function getErrorMessagesShort()
    {
        $errMsgs = [];
        foreach ($this->getErrors() as $error) {
            $errMsgs[] =
                $error[self::DPD_ERROR_DATA_MSG_SHORT_KEY].' (ErrorID='.$error[self::DPD_ERROR_DATA_ID_KEY].')';
        }

        return $errMsgs;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $response = [
            'Ack' => $this->isSuccessful(),
            'TimeStamp' => $this->getTimeStamp(),
            'Errors' => $this->getErrors(),
        ];

        return $response;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
