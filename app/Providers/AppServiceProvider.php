<?php

namespace App\Providers;

use App\Contracts\ChatAIProviderInterface;
use App\Events\NewSentimentAlert;
use App\Events\PatientRiskElevated;
use App\Events\VitalDataSynced;
use App\Listeners\SendDoctorNotification;
use App\Listeners\TriggerRiskAssessment;
use App\Services\AI\FallbackAIProvider;
use App\Services\AI\OpenAIProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ChatAIProviderInterface::class, function () {
            $apiKey = config('openai.api_key');

            return $apiKey ? new OpenAIProvider : new FallbackAIProvider;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiters();
        $this->registerEventListeners();
    }

    /**
     * Register event → listener mappings.
     */
    private function registerEventListeners(): void
    {
        Event::listen(VitalDataSynced::class, TriggerRiskAssessment::class);
        Event::listen(NewSentimentAlert::class, TriggerRiskAssessment::class);
        Event::listen(PatientRiskElevated::class, SendDoctorNotification::class);
    }

    /**
     * Configure rate limiters.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('openai', fn () => Limit::perMinute(20));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

