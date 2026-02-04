<?php

declare(strict_types=1);

namespace Tests\Ethers\Errors;

use Ethers\Errors\NonceExpiredError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class NonceExpiredErrorTest extends TestCase
{
    /**
     */
    public function test_it_should_have_correct_error_code(): void
    {
        $error = new NonceExpiredError();

        $this->assertSame('NONCE_EXPIRED', $error->code);
        $this->assertSame('NONCE_EXPIRED', NonceExpiredError::CODE);
    }

    /**
     */
    public function test_it_should_create_from_rpc_error_with_nonce_too_low(): void
    {
        $error = NonceExpiredError::fromRpcError(
            -32000,
            'nonce too low',
            ['nonce' => 5]
        );

        $this->assertSame('nonce has already been used', $error->getMessage());
        $this->assertSame('NONCE_EXPIRED', $error->code);
    }

    /**
     */
    public function test_it_should_create_from_rpc_error_with_nonce_too_high(): void
    {
        $error = NonceExpiredError::fromRpcError(
            -32000,
            'nonce too high',
            null
        );

        $this->assertSame('nonce too high', $error->getMessage());
    }

    /**
     */
    public function test_it_should_create_from_rpc_error_with_invalid_nonce(): void
    {
        $error = NonceExpiredError::fromRpcError(
            -32000,
            'invalid nonce',
            null
        );

        $this->assertSame('invalid nonce', $error->getMessage());
    }

    /**
     */
    public function test_it_should_not_handle_replacement_underpriced(): void
    {
        // replacement transaction underpriced 不应该由 NonceExpiredError 处理
        $error = NonceExpiredError::fromRpcError(
            -32000,
            'replacement transaction underpriced',
            null
        );

        // 应该返回原始消息，而不是映射为 "nonce has already been used"
        $this->assertSame('replacement transaction underpriced', $error->getMessage());
    }

    /**
     */
    public function test_it_should_store_transaction_info(): void
    {
        $transaction = ['nonce' => 10, 'from' => '0xabc...'];
        $error = new NonceExpiredError('test', $transaction);

        $this->assertSame($transaction, $error->transaction);
    }
}
