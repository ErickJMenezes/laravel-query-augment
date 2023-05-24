<?php

namespace ErickJMenezes\LaravelQueryAugment;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use ErickJMenezes\LaravelQueryAugment\Eloquent\Mixin;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * @throws \ReflectionException
     */
    public function register(): void
    {
        Builder::mixin(new Mixin());
    }
}
