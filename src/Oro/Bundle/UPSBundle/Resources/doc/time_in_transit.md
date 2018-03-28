# UPS time in transit

The `oro_ups.provider.cacheable_time_in_transit` service provides estimated time in transit based on the following:

 * a zip code of the shipping origin 
 * a zip code of the shipping destination
 * a pickup date

This information is kept in cache for 1 day. 

Example:

```code
/** @var UPSTransport $transport */
$transport = $upsChannel->getTransport();
$pickupDate = new \DateTime('+3 days');

$countryRepository = $doctrineHelper->getEntityRepositoryForClass(Country::class);
$country = $countryRepository->findOneBy(['iso2Code' => 'US']);

$timeInTransitProvider = $this->get('oro_ups.provider.cacheable_time_in_transit');

/** @var TimeInTransitResult $result */
$result = $timeInTransitProvider->getTimeInTransitResult(
    $transport,
    (new Address())->setPostalCode('90046')->setCountry($country),
    (new Address())->setPostalCode('66062')->setCountry($country),
    $pickupDate
);
```
As a result, you get a status of the estimated arrivals and other information. For more details, refer to [TimeInTransitResultInterface](../../TimeInTransit/Result/TimeInTransitResultInterface.php).
