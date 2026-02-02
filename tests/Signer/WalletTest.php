<?php

declare(strict_types=1);

namespace Tests\Ethers\Signer;

use Ethers\Signer\Wallet;
use PHPUnit\Framework\TestCase;

/**
 * Wallet 测试
 */
class WalletTest extends TestCase
{
    private string $testPrivateKey = '0x0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    /**
     * 测试钱包地址派生
     */
    public function test_address_derivation(): void
    {
        $wallet = new Wallet($this->testPrivateKey);

        $address = $wallet->getAddress();
        $this->assertStringStartsWith('0x', $address);
        $this->assertEquals(42, strlen($address));
    }

    /**
     * 测试带和不带 0x 前缀的私钥
     */
    public function test_private_key_with_and_without_prefix(): void
    {
        $privateKeyWithPrefix = '0x0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
        $privateKeyWithoutPrefix = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

        $wallet1 = new Wallet($privateKeyWithPrefix);
        $wallet2 = new Wallet($privateKeyWithoutPrefix);

        $this->assertEquals($wallet1->getAddress(), $wallet2->getAddress());
    }

    /**
     * 测试获取私钥
     */
    public function test_get_private_key(): void
    {
        $wallet = new Wallet($this->testPrivateKey);

        $privateKey = $wallet->getPrivateKey();
        $this->assertStringStartsWith('0x', $privateKey);
        $this->assertEquals(66, strlen($privateKey)); // 0x + 64 hex chars
    }

    /**
     * 测试随机钱包创建
     */
    public function test_create_random_wallet(): void
    {
        $wallet1 = Wallet::createRandom();
        $wallet2 = Wallet::createRandom();

        $this->assertNotEquals($wallet1->getPrivateKey(), $wallet2->getPrivateKey());
        $this->assertNotEquals($wallet1->getAddress(), $wallet2->getAddress());

        // 验证地址格式
        $this->assertStringStartsWith('0x', $wallet1->getAddress());
        $this->assertEquals(42, strlen($wallet1->getAddress()));
    }

    /**
     * 测试消息签名
     */
    public function test_sign_message(): void
    {
        $wallet = new Wallet($this->testPrivateKey);
        $signature = $wallet->signMessage('Hello World');

        $this->assertStringStartsWith('0x', $signature);
        $this->assertEquals(132, strlen($signature)); // 0x + 130 hex chars (65 bytes)
    }

    /**
     * 测试不同消息产生不同签名
     */
    public function test_different_messages_produce_different_signatures(): void
    {
        $wallet = new Wallet($this->testPrivateKey);

        $sig1 = $wallet->signMessage('Hello');
        $sig2 = $wallet->signMessage('World');

        $this->assertNotEquals($sig1, $sig2);
    }

    /**
     * 测试相同消息产生相同签名
     */
    public function test_same_message_produces_same_signature(): void
    {
        $wallet = new Wallet($this->testPrivateKey);

        $sig1 = $wallet->signMessage('Hello');
        $sig2 = $wallet->signMessage('Hello');

        $this->assertEquals($sig1, $sig2);
    }

    /**
     * 测试签名哈希
     */
    public function test_sign_hash(): void
    {
        $wallet = new Wallet($this->testPrivateKey);
        $hash = '0x'.str_repeat('ab', 32);

        $signature = $wallet->signHash($hash);

        $this->assertStringStartsWith('0x', $signature);
        $this->assertEquals(132, strlen($signature));
    }

    /**
     * 测试签名交易
     */
    public function test_sign_transaction(): void
    {
        $wallet = new Wallet($this->testPrivateKey);

        $signedTx = $wallet->signTransaction([
            'nonce' => 0,
            'gasPrice' => '20000000000',
            'gasLimit' => 21000,
            'to' => '0x1234567890123456789012345678901234567890',
            'value' => '1000000000000000000',
            'data' => '0x',
            'chainId' => 1,
        ]);

        $this->assertStringStartsWith('0x', $signedTx);
        $this->assertGreaterThan(100, strlen($signedTx));
    }

    /**
     * 测试没有 Provider 时获取余额抛出异常
     */
    public function test_get_balance_without_provider_throws_exception(): void
    {
        $wallet = new Wallet($this->testPrivateKey);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('需要连接 Provider 才能获取余额');

        $wallet->getBalance();
    }

    /**
     * 测试没有 Provider 时获取 nonce 抛出异常
     */
    public function test_get_nonce_without_provider_throws_exception(): void
    {
        $wallet = new Wallet($this->testPrivateKey);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('需要连接 Provider 才能获取 nonce');

        $wallet->getNonce();
    }

    /**
     * 测试没有 Provider 时发送交易抛出异常
     */
    public function test_send_transaction_without_provider_throws_exception(): void
    {
        $wallet = new Wallet($this->testPrivateKey);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('需要连接 Provider 才能发送交易');

        $wallet->sendTransaction([
            'to' => '0x1234567890123456789012345678901234567890',
            'value' => '1000000000000000000',
        ]);
    }

    /**
     * 测试 getProvider 没有连接时返回 null
     */
    public function test_get_provider_without_connection(): void
    {
        $wallet = new Wallet($this->testPrivateKey);
        $this->assertNull($wallet->getProvider());
    }

    /**
     * 测试不同私钥产生不同地址
     */
    public function test_different_private_keys_produce_different_addresses(): void
    {
        $wallet1 = new Wallet('0x'.str_repeat('01', 32));
        $wallet2 = new Wallet('0x'.str_repeat('02', 32));

        $this->assertNotEquals($wallet1->getAddress(), $wallet2->getAddress());
    }
}
