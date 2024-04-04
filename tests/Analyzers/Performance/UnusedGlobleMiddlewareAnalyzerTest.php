<?php

namespace Enlightn\Enlightn\Tests\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\UnusedGlobalMiddlewareAnalyzer;
use Enlightn\Enlightn\Tests\Analyzers\AnalyzerTestCase;
use Enlightn\Enlightn\Tests\Analyzers\Concerns\InteractsWithMiddleware;
use Enlightn\Enlightn\Tests\Middleware\DummyTrustProxiesL9;
use Enlightn\Enlightn\Tests\Middleware\UnusedTrustProxiesL9;
use Fruitcake\Cors\HandleCors;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Middleware\TrustHosts;

class UnusedGlobleMiddlewareAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithMiddleware;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(UnusedGlobalMiddlewareAnalyzer::class, $app);
    }

    /**
     * @test
     */
    public function passes_with_no_global_middleware()
    {
        $this->clearMiddleware();

        $this->runEnlightn();

        $this->assertPassed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    /**
     * @test
     */
    public function detects_trusted_hosts_without_trusted_proxies()
    {
        $kernel = $this->clearMiddleware();
        $kernel->pushMiddleware(TrustHosts::class);

        $this->runEnlightn();

        $this->assertFailed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    /**
     * @test
     */
    public function passes_with_wildcard_trusted_proxies()
    {
        $kernel = $this->clearMiddleware();
        $kernel->pushMiddleware(DummyTrustProxiesL9::class);

        $this->runEnlightn();

        $this->assertPassed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    /**
     * @test
     */
    public function detects_unused_trusted_proxies()
    {
        $kernel = $this->clearMiddleware();
        $kernel->pushMiddleware(UnusedTrustProxiesL9::class);

        $this->runEnlightn();

        $this->assertFailed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    /**
     * @test
     */
    public function detects_unused_cors()
    {
        $this->app->config->set('cors.paths', []);

        $kernel = $this->clearMiddleware();
        $kernel->pushMiddleware(HandleCors::class);

        $this->runEnlightn();

        $this->assertFailed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    private function clearMiddleware()
    {
        $kernel = $this->app->make(Kernel::class);
        if (method_exists($kernel, 'setGlobalMiddleware')) {
            $kernel->setGlobalMiddleware([]);
        }
        return $kernel;
    }
}
