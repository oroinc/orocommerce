<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Client\Result\UpsErrorResultTrait;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrivalInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResult;

/**
 * Creates result object from a response
 */
class TimeInTransitResultFactory implements TimeInTransitResultFactoryInterface
{
    use UpsErrorResultTrait;

    /**
     * @var EstimatedArrivalFactoryInterface
     */
    private $estimatedArrivalFactory;

    public function __construct(EstimatedArrivalFactoryInterface $estimatedArrivalFactory)
    {
        $this->estimatedArrivalFactory = $estimatedArrivalFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createResultByUpsClientResponse(RestResponseInterface $response)
    {
        $data = $response->json();
        if (!is_array($data)) {
            throw new \LogicException($data);
        }

        if ($data && array_key_exists('Fault', $data)) {
            return new TimeInTransitResult([
                TimeInTransitResult::STATUS_KEY => false,
                TimeInTransitResult::STATUS_DESCRIPTION_KEY => $this->getErrorMessage($data),
            ]);
        }

        $estimatedArrivals = [];

        // Parsing transit response data is important, so we should fire an exception if some elements are not found.
        try {
            // Define a shortcut to make lines shorter.
            $timeInTransitResponse =& $data['TimeInTransitResponse'];

            // A "1" normally indicates a successful response, whereas a "0" indicates a Transient or Hard error.
            $responseStatusCode = (string) $timeInTransitResponse['Response']['ResponseStatus']['Code'];
            $responseStatusDescription = $timeInTransitResponse['Response']['ResponseStatus']['Description'];

            // TransitResponse might not be present if shipping address is not valid.
            $serviceSummary = $timeInTransitResponse['TransitResponse']['ServiceSummary'] ?? [];

            if (isset($serviceSummary['EstimatedArrival'])) {
                $serviceSummary = [$timeInTransitResponse['TransitResponse']['ServiceSummary']];
            }

            foreach ($serviceSummary as $serviceTimeInTransit) {
                $estimatedArrival =& $serviceTimeInTransit['EstimatedArrival'];

                $arrivalDate = $this
                    ->parseArrivalDateTime($estimatedArrival['Arrival']['Date'], $estimatedArrival['Arrival']['Time']);

                if (!$arrivalDate instanceof \DateTime) {
                    throw new \LogicException('Could not parse arrival date time: ' . json_encode($estimatedArrival));
                }

                $estimatedArrivals[$serviceTimeInTransit['Service']['Code']] =
                    $this->createEstimatedArrival($arrivalDate, $estimatedArrival);
            }
        } catch (\Exception $e) {
            throw new \LogicException('Could not parse estimated arrivals: ' . $e->getMessage());
        }

        // AutoDutyCode is required for International requests, but is optional for others.
        $autoDutyCode = null;
        if (isset($data['TimeInTransitResponse']['TransitResponse']['AutoDutyCode'])) {
            $autoDutyCode = $data['TimeInTransitResponse']['TransitResponse']['AutoDutyCode'];
        }

        $customerContext = $this->getOptionalParameter('CustomerContext', $data);
        $transactionIdentifier = $this->getOptionalParameter('TransactionIdentifier', $data);

        return new TimeInTransitResult([
            TimeInTransitResult::STATUS_KEY => $responseStatusCode === '1',
            TimeInTransitResult::STATUS_DESCRIPTION_KEY => $responseStatusDescription,
            TimeInTransitResult::ESTIMATED_ARRIVALS_KEY => $estimatedArrivals,
            TimeInTransitResult::AUTO_DUTY_CODE_KEY => $autoDutyCode,
            TimeInTransitResult::CUSTOMER_CONTEXT_KEY => $customerContext,
            TimeInTransitResult::TRANSACTION_IDENTIFIER_KEY => $transactionIdentifier,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function createExceptionResult(RestException $exception)
    {
        $parameters = [
            TimeInTransitResult::STATUS_KEY => false,
            TimeInTransitResult::STATUS_DESCRIPTION_KEY => $exception->getMessage(),
        ];

        return new TimeInTransitResult($parameters);
    }

    /**
     * @param string $rawArrivalDate
     * @param string $rawArrivalTime
     *
     * @return bool|\DateTime
     */
    private function parseArrivalDateTime($rawArrivalDate, $rawArrivalTime)
    {
        return \DateTime::createFromFormat('Ymd His', sprintf('%s %s', $rawArrivalDate, $rawArrivalTime));
    }

    /**
     * @param \DateTime $arrivalDate
     * @param array     $estimatedArrival
     *
     * @return EstimatedArrivalInterface
     */
    private function createEstimatedArrival(\DateTime $arrivalDate, array $estimatedArrival)
    {
        return $this->estimatedArrivalFactory->createEstimatedArrival(
            $arrivalDate,
            $estimatedArrival['BusinessDaysInTransit'],
            $estimatedArrival['DayOfWeek'],
            $estimatedArrival['TotalTransitDays'] ?? null,
            $estimatedArrival['CustomerCenterCutoff'] ?? null
        );
    }

    /**
     * @param string $parameterName
     * @param array  $data
     *
     * @return null|array
     */
    private function getOptionalParameter(string $parameterName, array $data)
    {
        $result = null;
        if (isset($data['TimeInTransitResponse']['Response']['TransactionReference'][$parameterName])) {
            $result = $data['TimeInTransitResponse']['Response']['TransactionReference'][$parameterName];
        }

        return $result;
    }
}
