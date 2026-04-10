<?php

use App\Models\RiskThreshold;
use App\Services\RiskScoringService;
use Illuminate\Database\Eloquent\Collection;

/** @var RiskScoringService */
$service = null;

beforeEach(function () {
    $this->service = new RiskScoringService;
});

// ===========================================================================
// scoreToLevel
// ===========================================================================

it('maps score 0 to low risk level', function () {
    expect($this->service->scoreToLevel(0.0))->toBe('low');
});

it('maps score 30 to low risk level', function () {
    expect($this->service->scoreToLevel(30.0))->toBe('low');
});

it('maps score 31 to medium risk level', function () {
    expect($this->service->scoreToLevel(31.0))->toBe('medium');
});

it('maps score 60 to medium risk level', function () {
    expect($this->service->scoreToLevel(60.0))->toBe('medium');
});

it('maps score 61 to high risk level', function () {
    expect($this->service->scoreToLevel(61.0))->toBe('high');
});

it('maps score 80 to high risk level', function () {
    expect($this->service->scoreToLevel(80.0))->toBe('high');
});

it('maps score 81 to critical risk level', function () {
    expect($this->service->scoreToLevel(81.0))->toBe('critical');
});

it('maps score 100 to critical risk level', function () {
    expect($this->service->scoreToLevel(100.0))->toBe('critical');
});

// ===========================================================================
// scoreForFactor
// ===========================================================================

it('scores a value in the low band between 0 and 25', function () {
    // hrv threshold: low=60-100, medium=40-59.999, high=15-39.999
    $threshold = new RiskThreshold([
        'low_min' => 60.0,
        'low_max' => 100.0,
        'medium_min' => 40.0,
        'medium_max' => 59.999,
        'high_min' => 15.0,
        'high_max' => 39.999,
    ]);

    $score = $this->service->scoreForFactor(80.0, $threshold);

    expect($score)->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(25.0);
});

it('scores a value in the medium band between 26 and 65', function () {
    $threshold = new RiskThreshold([
        'low_min' => 60.0,
        'low_max' => 100.0,
        'medium_min' => 40.0,
        'medium_max' => 59.999,
        'high_min' => 15.0,
        'high_max' => 39.999,
    ]);

    $score = $this->service->scoreForFactor(50.0, $threshold);

    expect($score)->toBeGreaterThanOrEqual(26.0)->toBeLessThanOrEqual(65.0);
});

it('scores a value in the high band between 66 and 100', function () {
    $threshold = new RiskThreshold([
        'low_min' => 60.0,
        'low_max' => 100.0,
        'medium_min' => 40.0,
        'medium_max' => 59.999,
        'high_min' => 15.0,
        'high_max' => 39.999,
    ]);

    $score = $this->service->scoreForFactor(25.0, $threshold);

    expect($score)->toBeGreaterThanOrEqual(66.0)->toBeLessThanOrEqual(100.0);
});

it('returns 100 for a value below all defined ranges (worst risk)', function () {
    $threshold = new RiskThreshold([
        'low_min' => 60.0,
        'low_max' => 100.0,
        'medium_min' => 40.0,
        'medium_max' => 59.999,
        'high_min' => 15.0,
        'high_max' => 39.999,
    ]);

    // Value of 5 is below high_min (15) — worst case
    $score = $this->service->scoreForFactor(5.0, $threshold);

    expect($score)->toBe(100.0);
});

// ===========================================================================
// computeWeightedScore
// ===========================================================================

it('returns 0 when no factors are available', function () {
    /** @var Collection<string, RiskThreshold> $thresholds */
    $thresholds = new Collection;

    $score = $this->service->computeWeightedScore([
        'hrv' => null,
        'sleep_duration' => null,
        'sentiment' => null,
        'phq9' => null,
        'stress_index' => null,
    ], $thresholds);

    expect($score)->toBe(0.0);
});

it('computes weighted score correctly with partial factors', function () {
    $hrvThreshold = new RiskThreshold([
        'parameter_name' => 'hrv',
        'low_min' => 60.0,
        'low_max' => 100.0,
        'medium_min' => 40.0,
        'medium_max' => 59.999,
        'high_min' => 15.0,
        'high_max' => 39.999,
        'weight' => 0.3,
    ]);
    $hrvThreshold->parameter_name = 'hrv';

    $phq9Threshold = new RiskThreshold([
        'parameter_name' => 'phq9_score',
        'low_min' => 0.0,
        'low_max' => 9.0,
        'medium_min' => 10.0,
        'medium_max' => 19.0,
        'high_min' => 20.0,
        'high_max' => 27.0,
        'weight' => 0.15,
    ]);
    $phq9Threshold->parameter_name = 'phq9_score';

    /** @var Collection<string, RiskThreshold> $thresholds */
    $thresholds = new Collection(['hrv' => $hrvThreshold, 'phq9_score' => $phq9Threshold]);

    // hrv=80 (low band) — low risk score
    // phq9=25 (high band) — high risk score
    $score = $this->service->computeWeightedScore([
        'hrv' => 80.0,
        'sleep_duration' => null,
        'sentiment' => null,
        'phq9' => 25,
        'stress_index' => null,
    ], $thresholds);

    expect($score)->toBeGreaterThan(0.0)->toBeLessThanOrEqual(100.0);
});

it('normalizes score correctly when only one factor is available', function () {
    $hrvThreshold = new RiskThreshold([
        'parameter_name' => 'hrv',
        'low_min' => 60.0,
        'low_max' => 100.0,
        'medium_min' => 40.0,
        'medium_max' => 59.999,
        'high_min' => 15.0,
        'high_max' => 39.999,
        'weight' => 0.3,
    ]);

    /** @var Collection<string, RiskThreshold> $thresholds */
    $thresholds = new Collection(['hrv' => $hrvThreshold]);

    $score = $this->service->computeWeightedScore([
        'hrv' => 80.0,  // low band — should produce low score
        'phq9' => null,
    ], $thresholds);

    expect($score)->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(25.0);
});
