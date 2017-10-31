# UPS time in transit.

Service `oro_ups.provider.cacheable_time_in_transit` can get estimated time in transit and cached for 1 day, based on:
 * from zip code
 * to zip code
 * pickup date

For example:
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
In result you can get status of the interchange, estimated arrivals and other information. More details you can see in [TimeInTransitResultInterface](../../TimeInTransit/Result/TimeInTransitResultInterface.php).
