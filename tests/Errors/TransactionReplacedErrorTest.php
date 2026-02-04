<?php

declare(strict_types=1);

namespace Tests\Ethers\Errors;

use Ethers\Errors\TransactionReplacedError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TransactionReplacedErrorTest extends TestCase
{
    /**
     */
    public function test_it_should_have_correct_error_code(): void
    {
        $error = new TransactionReplacedError(
            '0x123...',
            '0xabc...',
            'replaced'
        );

        $this->assertSame('TRANSACTION_REPLACED', $error->code);
        $this->assertSame('TRANSACTION_REPLACED', TransactionReplacedError::CODE);
    }

    /**
     */
    public function test_it_should_store_transaction_hashes(): void
    {
        $error = new TransactionReplacedError(
            '0xoriginal...',
            '0xreplaced...',
            'replaced'
        );

        $this->assertSame('0xoriginal...', $error->originalHash);
        $this->assertSame('0xreplaced...', $error->replacedHash);
    }

    /**
     */
    public function test_it_should_have_different_messages_for_different_reasons(): void
    {
        $cancelled = new TransactionReplacedError('0x1', '0x2', 'cancelled');
        $replaced = new TransactionReplacedError('0x1', '0x2', 'replaced');
        $repriced = new TransactionReplacedError('0x1', '0x2', 'repriced');

        $this->assertSame('交易被取消', $cancelled->shortMessage);
        $this->assertSame('交易被替换', $replaced->shortMessage);
        $this->assertSame('交易被重新定价', $repriced->shortMessage);
    }

    /**
     */
    public function test_it_should_include_info_in_array(): void
    {
        $error = new TransactionReplacedError(
            '0xoriginal...',
            '0xreplaced...',
            'cancelled'
        );

        $array = $error->toArray();

        $this->assertSame('0xoriginal...', $array['info']['originalHash']);
        $this->assertSame('0xreplaced...', $array['info']['replacedHash']);
        $this->assertSame('cancelled', $array['info']['reason']);
    }
}
