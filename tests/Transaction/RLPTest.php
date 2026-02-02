<?php

declare(strict_types=1);

namespace Tests\Ethers\Transaction;

use Ethers\Transaction\RLP;
use PHPUnit\Framework\TestCase;

/**
 * RLP 编码测试
 */
class RLPTest extends TestCase
{
    /**
     * 测试空字符串编码
     */
    public function test_encode_empty_string(): void
    {
        $this->assertEquals('80', RLP::encode(''));
    }

    /**
     * 测试单字节编码 (< 0x80)
     */
    public function test_encode_single_byte(): void
    {
        $this->assertEquals('7f', RLP::encode('7f'));
        $this->assertEquals('00', RLP::encode('00'));
        $this->assertEquals('01', RLP::encode('01'));
    }

    /**
     * 测试短字符串编码 (长度 < 56)
     */
    public function test_encode_short_string(): void
    {
        // "dog" = 0x646f67
        $this->assertEquals('83646f67', RLP::encode('646f67'));
    }

    /**
     * 测试空列表编码
     */
    public function test_encode_empty_list(): void
    {
        $this->assertEquals('c0', RLP::encode([]));
    }

    /**
     * 测试单元素列表
     */
    public function test_encode_single_element_list(): void
    {
        // 646f67 = "dog" (3 bytes), 编码为 83646f67 (4 bytes)
        // 列表长度 4 bytes, c0 + 4 = c4
        $this->assertEquals('c483646f67', RLP::encode(['646f67']));
    }

    /**
     * 测试多元素列表
     */
    public function test_encode_multiple_element_list(): void
    {
        // ['cat', 'dog']
        $result = RLP::encode(['636174', '646f67']);
        $this->assertNotEmpty($result);
    }

    /**
     * 测试嵌套列表
     */
    public function test_encode_nested_list(): void
    {
        // [[], [[]], [[], [[]]]]
        $result = RLP::encode([[], [[]], [[], [[]]]]);
        $this->assertEquals('c7c0c1c0c3c0c1c0', $result);
    }

    /**
     * 测试整数编码
     */
    public function test_encode_integers(): void
    {
        // 0 -> 空字符串
        $this->assertEquals('80', RLP::encode(''));

        // 小整数
        $result = RLP::encode('0f'); // 15
        $this->assertEquals('0f', $result);

        // 较大整数
        $result = RLP::encode('0400'); // 1024
        $this->assertEquals('820400', $result);
    }

    /**
     * 测试交易格式的列表
     */
    public function test_encode_transaction_like_list(): void
    {
        // 简化的交易格式
        $txData = [
            '',           // nonce = 0
            '04a817c800', // gasPrice
            '5208',       // gasLimit
            '1234567890123456789012345678901234567890', // to
            '0de0b6b3a7640000', // value
            '',           // data
        ];

        $encoded = RLP::encode($txData);
        $this->assertNotEmpty($encoded);
        // 列表编码应该以 c 或 f 开头 (取决于长度)
        $this->assertTrue(
            str_starts_with($encoded, 'c') || str_starts_with($encoded, 'f') || str_starts_with($encoded, 'e')
        );
    }

    /**
     * 测试长字符串编码 (长度 >= 56)
     */
    public function test_encode_long_string(): void
    {
        // 创建一个超过 56 字节的字符串
        $longData = str_repeat('ab', 60); // 60 bytes
        $encoded = RLP::encode($longData);

        // 长字符串前缀是 0xb7 + length_of_length
        $this->assertTrue(strlen($encoded) > strlen($longData));
    }

    /**
     * 测试 EIP-1559 交易数据结构
     */
    public function test_eip1559_transaction_structure(): void
    {
        $txData = [
            '01',         // chainId
            '',           // nonce = 0
            '59682f00',   // maxPriorityFeePerGas
            '06fc23ac00', // maxFeePerGas
            '5208',       // gasLimit
            '1234567890123456789012345678901234567890', // to
            '0de0b6b3a7640000', // value
            '',           // data
            [],           // accessList
        ];

        $encoded = RLP::encode($txData);
        $this->assertNotEmpty($encoded);
    }
}
