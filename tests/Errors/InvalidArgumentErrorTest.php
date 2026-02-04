<?php

declare(strict_types=1);

namespace Tests\Ethers\Errors;

use Ethers\Errors\InvalidArgumentError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class InvalidArgumentErrorTest extends TestCase
{
    /**
     */
    public function test_it_should_have_correct_error_code(): void
    {
        $error = new InvalidArgumentError('test');

        $this->assertSame('INVALID_ARGUMENT', $error->code);
        $this->assertSame('INVALID_ARGUMENT', InvalidArgumentError::CODE);
    }

    /**
     */
    public function test_it_should_store_argument_and_value(): void
    {
        $error = new InvalidArgumentError('test', 'gasPrice', 'invalid');

        $this->assertSame('gasPrice', $error->argument);
        $this->assertSame('invalid', $error->value);
    }

    /**
     */
    public function test_it_should_create_for_argument_with_expected_type(): void
    {
        $error = InvalidArgumentError::forArgument('gasPrice', 'invalid', 'number or hex string');

        $this->assertSame("参数 'gasPrice' 无效，期望: number or hex string", $error->getMessage());
        $this->assertSame('gasPrice', $error->argument);
        $this->assertSame('invalid', $error->value);
    }

    /**
     */
    public function test_it_should_create_for_argument_without_expected_type(): void
    {
        $error = InvalidArgumentError::forArgument('to', null);

        $this->assertSame("参数 'to' 无效", $error->getMessage());
    }

    /**
     */
    public function test_it_should_include_argument_in_info(): void
    {
        $error = new InvalidArgumentError('test', 'address', '0xinvalid');
        $array = $error->toArray();

        $this->assertSame('address', $array['info']['argument']);
        $this->assertSame('0xinvalid', $array['info']['value']);
    }
}
