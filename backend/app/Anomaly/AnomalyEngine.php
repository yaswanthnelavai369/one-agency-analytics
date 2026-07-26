<?php

namespace App\Anomaly;

use App\Anomaly\Contracts\AnomalyDetectorInterface;
use App\Anomaly\Detectors\AdsAnomalyDetector;
use App\Anomaly\Detectors\ConversionAnomalyDetector;
use App\Anomaly\Detectors\GoalDeadlineDetector;
use App\Anomaly\Detectors\IntegrationHealthDetector;
use App\Anomaly\Detectors\RevenueAnomalyDetector;
use App\Anomaly\Detectors\SeoAnomalyDetector;
use App\Anomaly\Detectors\TrafficAnomalyDetector;
use App\Models\Client;

/**
 * Registry + orchestrator for every anomaly detector. Adding a detector is one
 * class (implementing AnomalyDetectorInterface) + one line in $detectorClasses.
 */
class AnomalyEngine
{
    /** @var class-string<AnomalyDetectorInterface>[] */
    protected array $detectorClasses = [
        TrafficAnomalyDetector::class,
        ConversionAnomalyDetector::class,
        RevenueAnomalyDetector::class,
        SeoAnomalyDetector::class,
        AdsAnomalyDetector::class,
        IntegrationHealthDetector::class,
        GoalDeadlineDetector::class,
    ];

    /** @return array<array> flat list of anomaly arrays from every detector */
    public function detect(Client $client): array
    {
        $anomalies = [];

        foreach ($this->detectorClasses as $class) {
            $detector = app($class);
            $anomalies = array_merge($anomalies, $detector->detect($client));
        }

        return $anomalies;
    }
}
