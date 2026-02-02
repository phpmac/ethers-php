<?php

declare(strict_types=1);

namespace Tests\Ethers\Contract;

use Ethers\Contract\Interface_;
use PHPUnit\Framework\TestCase;

/**
 * DecodeFunctionResult 测试
 * 验证 decodeFunctionResult 同时支持数字索引和名称键访问
 */
class DecodeFunctionResultTest extends TestCase
{
    /**
     * 测试单返回值 uint256 (类似 pendingReinvestHead)
     */
    public function test_single_uint256_return_value(): void
    {
        $interface = new Interface_([
            'function pendingReinvestHead() external view returns (uint256)',
        ]);

        // uint256 值 123456789 的 ABI 编码
        $encodedData = '0x00000000000000000000000000000000000000000000000000000000075bcd15';

        $result = $interface->decodeFunctionResult('pendingReinvestHead', $encodedData);

        // 验证同时存在数字索引和名称键
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('arg0', $result);

        // 验证值正确 (123456789 in hex is 0x75bcd15)
        $this->assertEquals('123456789', $result[0]);
        $this->assertEquals('123456789', $result['arg0']);
    }

    /**
     * 测试多返回值 (类似 processExpiredBonuses)
     */
    public function test_multiple_return_values(): void
    {
        $interface = new Interface_([
            'function processExpiredBonuses(uint256 count) external returns (uint256 processed, uint256 expiredAmount)',
        ]);

        // 两个 uint256 值: 50 (processed) 和 1000000000000000000 (expiredAmount)
        $encodedData = '0x'.
            '0000000000000000000000000000000000000000000000000000000000000032'. // 50
            '0000000000000000000000000000000000000000000000000de0b6b3a7640000'; // 10^18

        $result = $interface->decodeFunctionResult('processExpiredBonuses', $encodedData);

        // 验证数字索引
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals('50', $result[0]);
        $this->assertEquals('1000000000000000000', $result[1]);

        // 验证名称键
        $this->assertArrayHasKey('processed', $result);
        $this->assertArrayHasKey('expiredAmount', $result);
        $this->assertEquals('50', $result['processed']);
        $this->assertEquals('1000000000000000000', $result['expiredAmount']);
    }

    /**
     * 测试单返回值带名称 (类似 balanceOf)
     */
    public function test_single_named_return_value(): void
    {
        $interface = new Interface_([
            'function balanceOf(address owner) view returns (uint256 balance)',
        ]);

        // uint256 值 999999999999
        $encodedData = '0x000000000000000000000000000000000000000000000000000000e8d4a50fff';

        $result = $interface->decodeFunctionResult('balanceOf', $encodedData);

        // 验证同时存在数字索引和名称键
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('balance', $result);

        // 验证两者值相同
        $this->assertEquals('999999999999', $result[0]);
        $this->assertEquals('999999999999', $result['balance']);
    }

    /**
     * 测试字符串返回值
     */
    public function test_string_return_value(): void
    {
        $interface = new Interface_([
            'function name() view returns (string)',
        ]);

        // 字符串 "TestToken" 的 ABI 编码
        // offset (32 bytes) + length (32 bytes) + data
        $encodedData = '0x'.
            '0000000000000000000000000000000000000000000000000000000000000020'. // offset 32
            '0000000000000000000000000000000000000000000000000000000000000009'. // length 9
            '54657374546f6b656e0000000000000000000000000000000000000000000000'; // "TestToken" + padding

        $result = $interface->decodeFunctionResult('name', $encodedData);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('arg0', $result);
        $this->assertEquals('TestToken', $result[0]);
        $this->assertEquals('TestToken', $result['arg0']);
    }
}
