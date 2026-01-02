<?php

namespace App\Tests\Unit\Command;

use App\Command\BootstrapSetupCommand;
use PHPUnit\Framework\TestCase;

class BootstrapSetupCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $this->assertEquals('app:bootstrap:setup', BootstrapSetupCommand::getDefaultName());
    }

    public function testCommandDescription(): void
    {
        $reflection = new \ReflectionClass(BootstrapSetupCommand::class);
        $attributes = $reflection->getAttributes();

        $this->assertNotEmpty($attributes);
    }
}
