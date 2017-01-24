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
     * @var \ArrayObject
     */
    protected $values;

    /**
     * @var bool
     */
    protected $ack;

    /**
     * @var \DateTime
     */
    protected $timeStamp;

    /**
     * @var array
     */
    protected $errors;

    /**
     * @param array $values
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values = [])
    {
        $this->values = new \ArrayObject($values);
        $this->errors = [];

        if (!$this->values->offsetExists(self::DPD_ACK_KEY)) {
            throw new \InvalidArgumentException('No Ack parameter found in response data');
        }
        $this->ack = $this->values->offsetGet(self::DPD_ACK_KEY);

        if (!$this->values->offsetExists(self::DPD_TIMESTAMP_KEY)) {
            throw new \InvalidArgumentException('No TimeStamp parameter found in response data');
        }
        $this->timeStamp = new \DateTime($this->values->offsetGet(self::DPD_TIMESTAMP_KEY));

        if (!$this->isSuccessful()) {
            if (!$this->values->offsetExists(self::DPD_ERROR_DATA_LIST_KEY)) {
                throw new \InvalidArgumentException('No ErrorDataList parameter found in response data');
            }
            $this->errors = $this->values->offsetGet(self::DPD_ERROR_DATA_LIST_KEY);
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
     * @return \DateTime
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
}
