<?php

declare(strict_types=1);

namespace Tests\Ethers\Transaction;

use Ethers\Transaction\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * Transaction 测试
 */
class TransactionTest extends TestCase
{
    private string $privateKey = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    /**
     * 测试 Legacy 交易签名
     */
    public function test_legacy_transaction_sign(): void
    {
        $tx = new Transaction([
            'nonce' => 0,
            'gasPrice' => '20000000000', // 20 Gwei
            'gasLimit' => 21000,
            'to' => '0x1234567890123456789012345678901234567890',
            'value' => '1000000000000000000', // 1 ETH
            'data' => '0x',
            'chainId' => 1,
        ]);

        $signed = $tx->sign($this->privateKey);

        $this->assertStringStartsWith('0x', $signed);
        $this->assertGreaterThan(100, strlen($signed));
        // Legacy 交易不以 0x02 开头
        $this->assertStringStartsNotWith('0x02', $signed);
    }

    /**
     * 测试 EIP-1559 交易签名
     */
    public function test_eip1559_transaction_sign(): void
    {
        $tx = new Transaction([
            'nonce' => 0,
            'maxPriorityFeePerGas' => '1500000000', // 1.5 Gwei
            'maxFeePerGas' => '30000000000', // 30 Gwei
            'gasLimit' => 21000,
            'to' => '0x1234567890123456789012345678901234567890',
            'value' => '1000000000000000000', // 1 ETH
            'data' => '0x',
            'chainId' => 1,
        ]);

        $signed = $tx->sign($this->privateKey);

        $this->assertStringStartsWith('0x02', $signed); // EIP-1559 前缀
        $this->assertGreaterThan(100, strlen($signed));
    }

    /**
     * 测试合约调用交易
     */
    public function test_contract_call_transaction(): void
    {
        // transfer(address,uint256) 调用数据
        $data = '0xa9059cbb'.str_pad('1234567890123456789012345678901234567890', 64, '0', STR_PAD_LEFT)
            .str_pad(dechex(1000000), 64, '0', STR_PAD_LEFT);

        $tx = new Transaction([
            'nonce' => 5,
            'gasPrice' => '50000000000',
            'gasLimit' => 100000,
            'to' => '0xdac17f958d2ee523a2206206994597c13d831ec7', // USDT
            'value' => '0',
            'data' => $data,
            'chainId' => 1,
        ]);

        $signed = $tx->sign($this->privateKey);

        $this->assertStringStartsWith('0x', $signed);
        $this->assertGreaterThan(200, strlen($signed));
    }

    /**
     * 测试空 to 地址 (合约部署)
     */
    public function test_contract_deploy_transaction(): void
    {
        $bytecode = '0x608060405234801561001057600080fd5b50';

        $tx = new Transaction([
            'nonce' => 0,
            'gasPrice' => '20000000000',
            'gasLimit' => 500000,
            'to' => '', // 空地址表示合约部署
            'value' => '0',
            'data' => $bytecode,
            'chainId' => 1,
        ]);

        $signed = $tx->sign($this->privateKey);
        $this->assertStringStartsWith('0x', $signed);
    }

    /**
     * 测试不同 chainId
     */
    public function test_different_chain_ids(): void
    {
        $baseParams = [
            'nonce' => 0,
            'gasPrice' => '20000000000',
            'gasLimit' => 21000,
            'to' => '0x1234567890123456789012345678901234567890',
            'value' => '1000000000000000000',
            'data' => '0x',
        ];

        // Ethereum Mainnet
        $tx1 = new Transaction(array_merge($baseParams, ['chainId' => 1]));
        $signed1 = $tx1->sign($this->privateKey);

        // BSC
        $tx2 = new Transaction(array_merge($baseParams, ['chainId' => 56]));
        $signed2 = $tx2->sign($this->privateKey);

        // Polygon
        $tx3 = new Transaction(array_merge($baseParams, ['chainId' => 137]));
        $signed3 = $tx3->sign($this->privateKey);

        // 不同 chainId 应该产生不同的签名
        $this->assertNotEquals($signed1, $signed2);
        $this->assertNotEquals($signed1, $signed3);
        $this->assertNotEquals($signed2, $signed3);
    }

    /**
     * 测试带 0x 前缀的私钥
     */
    public function test_private_key_with_prefix(): void
    {
        $tx = new Transaction([
            'nonce' => 0,
            'gasPrice' => '20000000000',
            'gasLimit' => 21000,
            'to' => '0x1234567890123456789012345678901234567890',
            'value' => '1000000000000000000',
            'data' => '0x',
            'chainId' => 1,
        ]);

        // 带前缀
        $signed1 = $tx->sign('0x'.$this->privateKey);
        // 不带前缀
        $signed2 = $tx->sign($this->privateKey);

        $this->assertEquals($signed1, $signed2);
    }

    /**
     * 测试十六进制格式的值
     */
    public function test_hex_values(): void
    {
        $tx = new Transaction([
            'nonce' => '0x0',
            'gasPrice' => '0x4a817c800', // 20 Gwei
            'gasLimit' => '0x5208', // 21000
            'to' => '0x1234567890123456789012345678901234567890',
            'value' => '0xde0b6b3a7640000', // 1 ETH
            'data' => '0x',
            'chainId' => 1,
        ]);

        $signed = $tx->sign($this->privateKey);
        $this->assertStringStartsWith('0x', $signed);
    }
}
