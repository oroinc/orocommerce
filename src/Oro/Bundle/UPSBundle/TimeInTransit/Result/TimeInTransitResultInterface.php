<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result;

interface TimeInTransitResultInterface
{
    /**
     * Identifies the success or failure of the interchange.
     *
     * @return bool
     */
    public function getStatus();

    /**
     * Describes the Response Status Code.
     *
     * @return string
     */
    public function getStatusDescription();

    /**
     * @return EstimatedArrivalInterface[]
     */
    public function getEstimatedArrivals();

    /**
     * @param string $serviceCode
     *
     * @return EstimatedArrivalInterface|null
     */
    public function getEstimatedArrivalByService($serviceCode);

    /**
     * Required output for International requests. If Documents indicator is set for Non-document a duty is
     * automatically calculated.
     * The possible values to be returned are:
     * 01 = Dutiable
     * 02 = Non-Dutiabl
     * 03 = Low-value
     * 04 = Courier Remission
     * 05 = Gift
     * 06 = Military
     * 07 = Exception
     * 08 = Line Release
     * 09 = Section 321 low value
     *
     * @return string
     */
    public function getAutoDutyCode();

    /**
     * Customer provided data. If this data is present in the request, it is echoed back to the customer.
     *
     * @return string
     */
    public function getCustomerContext();

    /**
     * Customer provided data. If this data is present in the request, it is echoed back to the customer.
     *
     * @return string
     */
    public function getTransactionIdentifier();
}
