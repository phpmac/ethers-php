<?php

declare(strict_types=1);

namespace Ethers\Tests;

use Ethers\Contract\ContractFactory;
use Ethers\Contract\Interface_;
use Ethers\Ethers;
use PHPUnit\Framework\TestCase;

/**
 * Ethers 主类测试
 */
class EthersTest extends TestCase
{
    /**
     * 测试 parseEther / formatEther
     */
    public function test_ether_conversion(): void
    {
        $this->assertEquals('1000000000000000000', Ethers::parseEther('1'));
        $this->assertEquals('1', Ethers::formatEther('1000000000000000000'));
    }

    /**
     * 测试 parseUnits / formatUnits
     */
    public function test_units_conversion(): void
    {
        $this->assertEquals('1000000', Ethers::parseUnits('1', 6));
        $this->assertEquals('1', Ethers::formatUnits('1000000', 6));
    }

    /**
     * 测试函数选择器
     */
    public function test_function_selector(): void
    {
        $this->assertEquals('0xa9059cbb', Ethers::id('transfer(address,uint256)'));
    }

    /**
     * 测试 keccak256
     */
    public function test_keccak256(): void
    {
        $hash = Ethers::keccak256('Hello');
        $this->assertStringStartsWith('0x', $hash);
        $this->assertEquals(66, strlen($hash));
    }

    /**
     * 测试地址验证
     */
    public function test_is_address(): void
    {
        $this->assertTrue(Ethers::isAddress('0x1234567890123456789012345678901234567890'));
        $this->assertFalse(Ethers::isAddress('invalid'));
        $this->assertFalse(Ethers::isAddress('0x123')); // 太短
        $this->assertFalse(Ethers::isAddress('1234567890123456789012345678901234567890')); // 无前缀
    }

    /**
     * 测试地址校验和
     */
    public function test_checksum_address(): void
    {
        $address = '0xfb6916095ca1df60bb79ce92ce3ea74c37c5d359';
        $checksummed = Ethers::getAddress($address);

        $this->assertNotEquals($address, $checksummed);
        $this->assertEquals(strtolower($address), strtolower($checksummed));
    }

    /**
     * 测试常量地址和哈希
     */
    public function test_constants(): void
    {
        $this->assertEquals('0x0000000000000000000000000000000000000000', Ethers::zeroAddress());
        $this->assertEquals('0x0000000000000000000000000000000000000000000000000000000000000000', Ethers::zeroHash());
    }

    /**
     * 测试 Hex 转换
     */
    public function test_hex_conversion(): void
    {
        $this->assertEquals('0xde0b6b3a7640000', Ethers::toBeHex('1000000000000000000'));
        $this->assertEquals('1000000000000000000', Ethers::toBigInt('0xde0b6b3a7640000'));
    }

    /**
     * 测试 wallet 创建
     */
    public function test_wallet_creation(): void
    {
        $wallet = Ethers::wallet('0x0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef');
        $this->assertStringStartsWith('0x', $wallet->getAddress());
        $this->assertEquals(42, strlen($wallet->getAddress()));
    }

    /**
     * 测试随机钱包创建
     */
    public function test_create_random_wallet(): void
    {
        $wallet1 = Ethers::createRandomWallet();
        $wallet2 = Ethers::createRandomWallet();

        $this->assertNotEquals($wallet1->getAddress(), $wallet2->getAddress());
    }

    /**
     * 测试 contract 创建
     */
    public function test_contract_creation(): void
    {
        $contract = Ethers::contract(
            '0x1234567890123456789012345678901234567890',
            ['function name() view returns (string)']
        );

        $this->assertEquals('0x1234567890123456789012345678901234567890', $contract->target);
    }

    /**
     * 测试 contractFactory 创建
     */
    public function test_contract_factory_creation(): void
    {
        $factory = Ethers::contractFactory(
            ['function name() view returns (string)'],
            '0x608060405234801561001057600080fd5b50'
        );

        $this->assertInstanceOf(ContractFactory::class, $factory);
    }

    /**
     * 测试 parseAbi
     */
    public function test_parse_abi(): void
    {
        $interface = Ethers::parseAbi([
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        $this->assertInstanceOf(Interface_::class, $interface);
        $this->assertNotNull($interface->getFunction('transfer'));
    }

    /**
     * 测试 interface 创建
     */
    public function test_interface_creation(): void
    {
        $interface = Ethers::interface([
            [
                'type' => 'function',
                'name' => 'name',
                'inputs' => [],
                'outputs' => [['type' => 'string']],
                'stateMutability' => 'view',
            ],
        ]);

        $this->assertInstanceOf(Interface_::class, $interface);
    }

    /**
     * 测试 abiCoder 创建
     */
    public function test_abi_coder_creation(): void
    {
        $coder = Ethers::abiCoder();
        $encoded = $coder->encode(['uint256'], ['100']);
        $this->assertStringStartsWith('0x', $encoded);
    }

    /**
     * 测试无效地址抛出异常
     */
    public function test_invalid_address_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Ethers::getAddress('invalid');
    }
}
