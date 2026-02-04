<?php

declare(strict_types=1);

namespace Tests\Ethers\Errors;

use Ethers\Errors\CancelledError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class CancelledErrorTest extends TestCase
{
    /**
     */
    public function test_it_should_have_correct_error_code(): void
    {
        $error = new CancelledError();

        $this->assertSame('CANCELLED', $error->code);
        $this->assertSame('CANCELLED', CancelledError::CODE);
    }

    /**
     */
    public function test_it_should_create_user_cancelled_error(): void
    {
        $error = CancelledError::userCancelled('用户拒绝签名');

        $this->assertSame('用户拒绝签名', $error->getMessage());
        $this->assertSame('user_cancelled', $error->cancelReason);
    }

    /**
     */
    public function test_it_should_create_timeout_cancelled_error(): void
    {
        $error = CancelledError::timeoutCancelled(30);

        $this->assertSame('操作在 30 秒后超时取消', $error->getMessage());
        $this->assertSame('timeout', $error->cancelReason);
        $this->assertSame(30, $error->info['timeout']);
    }

    /**
     */
    public function test_it_should_store_cause(): void
    {
        $cause = new \Exception('原始错误');
        $error = new CancelledError('操作已取消', 'test_reason', $cause);

        $this->assertSame($cause, $error->cause);
        $this->assertSame('原始错误', $error->info['cause']);
    }

    /**
     */
    public function test_it_should_have_default_message(): void
    {
        $error = new CancelledError();

        $this->assertSame('操作已取消', $error->getMessage());
        $this->assertSame('操作已取消', $error->shortMessage);
    }
}
