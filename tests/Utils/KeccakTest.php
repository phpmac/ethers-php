<?php

declare(strict_types=1);

namespace Tests\Ethers\Utils;

use Ethers\Utils\Keccak;
use PHPUnit\Framework\TestCase;

/**
 * Keccak 哈希测试
 */
class KeccakTest extends TestCase
{
    /**
     * 测试字符串哈希
     */
    public function test_hash(): void
    {
        $hash = Keccak::hash('Hello');
        $this->assertStringStartsWith('0x', $hash);
        $this->assertEquals(66, strlen($hash)); // 0x + 64 hex chars
    }

    /**
     * 测试已知哈希值
     */
    public function test_known_hash_values(): void
    {
        // 空字符串的 keccak256
        $emptyHash = Keccak::hash('');
        $this->assertEquals(
            '0xc5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470',
            $emptyHash
        );
    }

    /**
     * 测试函数选择器
     */
    public function test_function_selector(): void
    {
        // ERC20 transfer
        $selector = Keccak::functionSelector('transfer(address,uint256)');
        $this->assertEquals('0xa9059cbb', $selector);

        // ERC20 approve
        $selector = Keccak::functionSelector('approve(address,uint256)');
        $this->assertEquals('0x095ea7b3', $selector);

        // ERC20 balanceOf
        $selector = Keccak::functionSelector('balanceOf(address)');
        $this->assertEquals('0x70a08231', $selector);

        // ERC20 totalSupply
        $selector = Keccak::functionSelector('totalSupply()');
        $this->assertEquals('0x18160ddd', $selector);
    }

    /**
     * 测试事件主题
     */
    public function test_event_topic(): void
    {
        // ERC20 Transfer event
        $topic = Keccak::eventTopic('Transfer(address,address,uint256)');
        $this->assertStringStartsWith('0x', $topic);
        $this->assertEquals(66, strlen($topic));
        $this->assertEquals(
            '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef',
            $topic
        );

        // ERC20 Approval event
        $topic = Keccak::eventTopic('Approval(address,address,uint256)');
        $this->assertEquals(
            '0x8c5be1e5ebec7d5bd14f71427d1e84f3dd0314c0f7b2291e5b200ac8c7c3b925',
            $topic
        );
    }

    /**
     * 测试十六进制输入哈希
     */
    public function test_hex_hash(): void
    {
        // 哈希十六进制数据
        $hash = Keccak::hashHex('0x1234');
        $this->assertStringStartsWith('0x', $hash);
        $this->assertEquals(66, strlen($hash));
    }
}
