<?php

declare(strict_types=1);

namespace Tests\Ethers\Contract;

use Ethers\Contract\AbiCoder;
use PHPUnit\Framework\TestCase;

/**
 * AbiCoder 测试
 */
class AbiCoderTest extends TestCase
{
    private AbiCoder $coder;

    protected function setUp(): void
    {
        $this->coder = new AbiCoder;
    }

    /**
     * 测试编码地址
     */
    public function test_encode_address(): void
    {
        $encoded = $this->coder->encode(
            ['address'],
            ['0x1234567890123456789012345678901234567890']
        );
        $this->assertStringStartsWith('0x', $encoded);
        $this->assertEquals(66, strlen($encoded)); // 0x + 64 hex chars
    }

    /**
     * 测试编码 uint256
     */
    public function test_encode_uint256(): void
    {
        $encoded = $this->coder->encode(['uint256'], ['1000000000000000000']);
        $this->assertStringStartsWith('0x', $encoded);
        $this->assertEquals(66, strlen($encoded));
    }

    /**
     * 测试编码多个参数
     */
    public function test_encode_multiple_params(): void
    {
        $encoded = $this->coder->encode(
            ['address', 'uint256'],
            ['0x1234567890123456789012345678901234567890', '1000000000000000000']
        );
        $this->assertEquals(130, strlen($encoded)); // 0x + 64 + 64
    }

    /**
     * 测试编码函数调用
     */
    public function test_encode_function_call(): void
    {
        $data = $this->coder->encodeFunctionCall(
            'transfer(address,uint256)',
            ['0x1234567890123456789012345678901234567890', '1000000000000000000']
        );
        $this->assertStringStartsWith('0xa9059cbb', $data); // transfer selector
    }

    /**
     * 测试解码 uint256
     */
    public function test_decode_uint256(): void
    {
        $data = '0x'.str_pad(dechex(12345), 64, '0', STR_PAD_LEFT);
        $decoded = $this->coder->decode(['uint256'], $data);
        $this->assertEquals('12345', $decoded[0]);
    }

    /**
     * 测试解码 address
     */
    public function test_decode_address(): void
    {
        $data = '0x'.str_pad('1234567890123456789012345678901234567890', 64, '0', STR_PAD_LEFT);
        $decoded = $this->coder->decode(['address'], $data);
        $this->assertEquals('0x1234567890123456789012345678901234567890', $decoded[0]);
    }

    /**
     * 测试编码/解码 bool
     */
    public function test_bool(): void
    {
        $encodedTrue = $this->coder->encode(['bool'], [true]);
        $encodedFalse = $this->coder->encode(['bool'], [false]);

        $decodedTrue = $this->coder->decode(['bool'], $encodedTrue);
        $decodedFalse = $this->coder->decode(['bool'], $encodedFalse);

        $this->assertTrue($decodedTrue[0]);
        $this->assertFalse($decodedFalse[0]);
    }

    /**
     * 测试编码/解码 bytes
     */
    public function test_dynamic_bytes(): void
    {
        $data = '0x1234567890';
        $encoded = $this->coder->encode(['bytes'], [$data]);
        $decoded = $this->coder->decode(['bytes'], $encoded);
        $this->assertEquals($data, $decoded[0]);
    }

    /**
     * 测试编码/解码 string
     */
    public function test_string(): void
    {
        $str = 'Hello, World!';
        $encoded = $this->coder->encode(['string'], [$str]);
        $decoded = $this->coder->decode(['string'], $encoded);
        $this->assertEquals($str, $decoded[0]);
    }

    /**
     * 测试编码/解码 bytes32
     */
    public function test_bytes32(): void
    {
        $data = '0x'.str_repeat('ab', 32);
        $encoded = $this->coder->encode(['bytes32'], [$data]);
        $decoded = $this->coder->decode(['bytes32'], $encoded);
        $this->assertEquals($data, $decoded[0]);
    }

    /**
     * 测试编码/解码数组
     */
    public function test_dynamic_array(): void
    {
        $values = ['100', '200', '300'];
        $encoded = $this->coder->encode(['uint256[]'], [$values]);
        $decoded = $this->coder->decode(['uint256[]'], $encoded);
        $this->assertEquals($values, $decoded[0]);
    }

    /**
     * 测试复杂类型组合
     */
    public function test_complex_types(): void
    {
        $types = ['address', 'uint256', 'bool'];
        $values = [
            '0x1234567890123456789012345678901234567890',
            '1000000000000000000',
            true,
        ];

        $encoded = $this->coder->encode($types, $values);
        $decoded = $this->coder->decode($types, $encoded);

        $this->assertEquals($values[0], $decoded[0]);
        $this->assertEquals($values[1], $decoded[1]);
        $this->assertEquals($values[2], $decoded[2]);
    }

    /**
     * 测试有符号整数
     */
    public function test_signed_int(): void
    {
        // 正数
        $encoded = $this->coder->encode(['int256'], ['100']);
        $decoded = $this->coder->decode(['int256'], $encoded);
        $this->assertEquals('100', $decoded[0]);

        // 负数
        $encoded = $this->coder->encode(['int256'], ['-100']);
        $decoded = $this->coder->decode(['int256'], $encoded);
        $this->assertEquals('-100', $decoded[0]);
    }
}
