<?php

declare(strict_types=1);

namespace Tests\Ethers\Utils;

use Ethers\Utils\Hex;
use PHPUnit\Framework\TestCase;

/**
 * Hex 工具类测试
 */
class HexTest extends TestCase
{
    /**
     * 测试前缀处理
     */
    public function test_prefix(): void
    {
        $this->assertEquals('0x1234', Hex::prefix('1234'));
        $this->assertEquals('0x1234', Hex::prefix('0x1234'));
    }

    /**
     * 测试移除前缀
     */
    public function test_strip_prefix(): void
    {
        $this->assertEquals('1234', Hex::stripPrefix('0x1234'));
        $this->assertEquals('1234', Hex::stripPrefix('1234'));
    }

    /**
     * 测试整数转换
     */
    public function test_int_conversion(): void
    {
        $this->assertEquals('0xff', Hex::fromInt(255));
        $this->assertEquals(255, Hex::toInt('0xff'));
        $this->assertEquals(0, Hex::toInt('0x0'));
        $this->assertEquals(16, Hex::toInt('0x10'));
    }

    /**
     * 测试 BigInt 转换
     */
    public function test_big_int_conversion(): void
    {
        $this->assertEquals('1000000000000000000', Hex::toBigInt('0xde0b6b3a7640000'));
        $this->assertEquals('0xde0b6b3a7640000', Hex::fromBigInt('1000000000000000000'));
        $this->assertEquals('0', Hex::toBigInt('0x0'));
        $this->assertEquals('0x0', Hex::fromBigInt('0'));
    }

    /**
     * 测试左填充
     */
    public function test_pad_left(): void
    {
        $this->assertEquals('0x0000001234', Hex::padLeft('0x1234', 10));
        $this->assertEquals('0x00000000', Hex::padLeft('0x0', 8));
    }

    /**
     * 测试右填充
     */
    public function test_pad_right(): void
    {
        $this->assertEquals('0x1234000000', Hex::padRight('0x1234', 10));
    }

    /**
     * 测试大数值
     */
    public function test_large_numbers(): void
    {
        // 2^256 - 1 (max uint256)
        $maxUint256 = 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';
        $decimal = Hex::toBigInt('0x'.$maxUint256);
        $this->assertNotEmpty($decimal);

        // 验证往返转换
        $backToHex = Hex::fromBigInt($decimal);
        $this->assertEquals('0x'.$maxUint256, $backToHex);
    }
}
