<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class EnvironmentEncryptCommandTest extends TestCase
{
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = m::spy(Filesystem::class);
        $this->filesystem->shouldReceive('get')
            ->andReturn(true)
            ->shouldReceive('put')
            ->andReturn('APP_NAME=Laravel');
        File::swap($this->filesystem);
    }

    public function testItFailsWithInvalidCipherFails()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--cipher' => 'invalid'])
            ->expectsOutputToContain('Unsupported cipher')
            ->assertExitCode(1);
    }

    public function testItFailsUsingCipherWithInvalidKey()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--cipher' => 'aes-128-cbc', '--key' => 'invalid'])
            ->expectsOutputToContain('incorrect key length')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheCorrectFileWhenUsingEnvironment()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--env' => 'production'])
            ->expectsOutputToContain('.env.production.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.production.encrypted'), m::any());
    }

    public function testItGeneratesTheCorrectFileWhenNotUsingEnvironment()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get');

        $this->artisan('env:encrypt')
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.encrypted'), m::any());
    }

    public function testItFailsWhenEnvironmentFileCannotBeFound()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('env:encrypt')
            ->expectsOutputToContain('Environment file not found.')
            ->assertExitCode(1);
    }

    public function testItFailsWhenEncyptionFileExists()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);

        $this->artisan('env:encrypt')
            ->expectsOutputToContain('Encrypted environment file already exists.')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheEncryptionFileWhenForcing()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->artisan('env:encrypt', ['--force' => true])
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.encrypted'), m::any());
    }
}
