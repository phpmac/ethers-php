<?php

declare(strict_types=1);

namespace Tests\Ethers\Errors;

use Ethers\Errors\UnknownError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class UnknownErrorTest extends TestCase
{
    /**
     */
    public function test_it_should_have_correct_error_code(): void
    {
        $error = new UnknownError();

        $this->assertSame('UNKNOWN_ERROR', $error->code);
        $this->assertSame('UNKNOWN_ERROR', UnknownError::CODE);
    }

    /**
     */
    public function test_it_should_store_original_error(): void
    {
        $error = new UnknownError('message', 'original error info');

        $this->assertSame('original error info', $error->originalError);
    }

    /**
     */
    public function test_it_should_create_from_exception(): void
    {
        $cause = new \Exception('原始异常', 123);
        $error = UnknownError::fromException($cause);

        $this->assertSame('原始异常', $error->getMessage());
        $this->assertStringContainsString('Exception: 原始异常', $error->originalError);
        $this->assertArrayHasKey('file', $error->info);
        $this->assertArrayHasKey('line', $error->info);
        $this->assertArrayHasKey('trace', $error->info);
    }

    /**
     */
    public function test_it_should_have_default_message(): void
    {
        $error = new UnknownError();

        $this->assertSame('未知错误', $error->getMessage());
        $this->assertSame('未知错误', $error->shortMessage);
    }

    /**
     */
    public function test_it_should_convert_to_array(): void
    {
        $error = new UnknownError('custom message', 'original');
        $array = $error->toArray();

        $this->assertSame('UNKNOWN_ERROR', $array['code']);
        $this->assertSame('custom message', $array['message']);
        $this->assertSame('original', $array['info']['originalError']);
    }
}
