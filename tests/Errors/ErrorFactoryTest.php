<?php

declare(strict_types=1);

namespace Tests\Ethers\Errors;

use Ethers\Errors\ErrorFactory;
use Ethers\Errors\InsufficientFundsError;
use Ethers\Errors\NonceExpiredError;
use Ethers\Errors\ReplacementUnderpricedError;
use Ethers\Errors\ServerError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ErrorFactoryTest extends TestCase
{
    /**
     */
    public function test_it_should_create_replacement_underpriced_error(): void
    {
        $exception = ErrorFactory::fromRpcError(
            -32000,
            'replacement transaction underpriced',
            ['nonce' => 10]
        );

        $this->assertInstanceOf(ReplacementUnderpricedError::class, $exception);
        $this->assertSame('REPLACEMENT_UNDERPRICED', $exception->code);
        $this->assertSame('replacement transaction underpriced', $exception->getMessage());
    }

    /**
     */
    public function test_it_should_create_nonce_expired_error_for_nonce_too_low(): void
    {
        $exception = ErrorFactory::fromRpcError(
            -32000,
            'nonce too low',
            ['nonce' => 5]
        );

        $this->assertInstanceOf(NonceExpiredError::class, $exception);
        $this->assertSame('NONCE_EXPIRED', $exception->code);
        $this->assertSame('nonce has already been used', $exception->getMessage());
    }

    /**
     */
    public function test_it_should_create_nonce_expired_error_for_nonce_too_high(): void
    {
        $exception = ErrorFactory::fromRpcError(
            -32000,
            'nonce too high',
            ['nonce' => 100]
        );

        $this->assertInstanceOf(NonceExpiredError::class, $exception);
        $this->assertSame('NONCE_EXPIRED', $exception->code);
        $this->assertSame('nonce too high', $exception->getMessage());
    }

    /**
     */
    public function test_it_should_create_insufficient_funds_error(): void
    {
        $exception = ErrorFactory::fromRpcError(
            -32000,
            'insufficient funds for gas * price + value',
            ['value' => '1000000000000000000']
        );

        $this->assertInstanceOf(InsufficientFundsError::class, $exception);
        $this->assertSame('INSUFFICIENT_FUNDS', $exception->code);
        $this->assertSame('insufficient funds', $exception->getMessage());
    }

    /**
     */
    public function test_it_should_create_server_error_for_unknown_rpc_error(): void
    {
        $exception = ErrorFactory::fromRpcError(
            -32603,
            'Internal error',
            null
        );

        $this->assertInstanceOf(ServerError::class, $exception);
        $this->assertSame('SERVER_ERROR', $exception->code);
    }

    /**
     */
    public function test_it_should_handle_case_insensitive_rpc_messages(): void
    {
        $exception = ErrorFactory::fromRpcError(
            -32000,
            'REPLACEMENT TRANSACTION UNDERPRICED',
            null
        );

        $this->assertInstanceOf(ReplacementUnderpricedError::class, $exception);
    }
}
