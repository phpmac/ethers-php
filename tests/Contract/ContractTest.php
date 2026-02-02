<?php

declare(strict_types=1);

namespace Tests\Ethers\Contract;

use Ethers\Contract\Contract;
use Ethers\Contract\ContractFunction;
use PHPUnit\Framework\TestCase;

/**
 * Contract 测试
 */
class ContractTest extends TestCase
{
    /**
     * 测试使用 JSON ABI 创建合约
     */
    public function test_create_with_json_abi(): void
    {
        $address = '0x1234567890123456789012345678901234567890';
        $abi = [
            [
                'type' => 'function',
                'name' => 'name',
                'inputs' => [],
                'outputs' => [['name' => '', 'type' => 'string']],
                'stateMutability' => 'view',
            ],
        ];

        $contract = new Contract($address, $abi);

        $this->assertEquals(strtolower($address), $contract->target);
        $this->assertEquals(strtolower($address), $contract->getAddress());
    }

    /**
     * 测试使用人类可读 ABI 创建合约
     */
    public function test_create_with_human_readable_abi(): void
    {
        $address = '0x1234567890123456789012345678901234567890';
        $abi = [
            'function name() view returns (string)',
            'function balanceOf(address) view returns (uint256)',
            'function transfer(address to, uint256 amount) returns (bool)',
        ];

        $contract = new Contract($address, $abi);

        $this->assertEquals(strtolower($address), $contract->target);
    }

    /**
     * 测试 getFunction
     */
    public function test_get_function(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'function name() view returns (string)',
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        $nameFunc = $contract->getFunction('name');
        $this->assertInstanceOf(ContractFunction::class, $nameFunc);
        $this->assertEquals('name', $nameFunc->getName());
        $this->assertEquals('view', $nameFunc->getStateMutability());

        $transferFunc = $contract->getFunction('transfer');
        $this->assertNotNull($transferFunc);
        $this->assertEquals('nonpayable', $transferFunc->getStateMutability());
    }

    /**
     * 测试 ContractFunction 属性
     */
    public function test_contract_function_properties(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        $func = $contract->getFunction('transfer');
        $this->assertInstanceOf(ContractFunction::class, $func);
        $this->assertEquals('transfer', $func->getName());
        $this->assertEquals('0xa9059cbb', $func->getSelector());
        $this->assertEquals('nonpayable', $func->getStateMutability());
        $this->assertCount(2, $func->getInputs());
        $this->assertCount(1, $func->getOutputs());
    }

    /**
     * 测试 getEvent
     */
    public function test_get_event(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'event Transfer(address indexed from, address indexed to, uint256 value)',
        ]);

        $event = $contract->getEvent('Transfer');
        $this->assertNotNull($event);
        $this->assertEquals('Transfer', $event['name']);
    }

    /**
     * 测试 encodeFunction
     */
    public function test_encode_function(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        $data = $contract->encodeFunction('transfer', [
            '0x0000000000000000000000000000000000000001',
            '1000000000000000000',
        ]);

        $this->assertStringStartsWith('0xa9059cbb', $data);
    }

    /**
     * 测试 getInterface
     */
    public function test_get_interface(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'function name() view returns (string)',
        ]);

        $interface = $contract->getInterface();
        $this->assertNotNull($interface);
        $this->assertNotNull($interface->getFunction('name'));
    }

    /**
     * 测试没有 Provider 时获取返回 null
     */
    public function test_get_provider_without_provider(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'function name() view returns (string)',
        ]);

        $this->assertNull($contract->getProvider());
        $this->assertNull($contract->getSigner());
        $this->assertNull($contract->getRunner());
    }

    /**
     * 测试通过属性访问函数
     */
    public function test_access_function_as_property(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        $func = $contract->transfer;
        $this->assertInstanceOf(ContractFunction::class, $func);
        $this->assertEquals('transfer', $func->getName());
    }

    /**
     * 测试 multicall
     */
    public function test_multicall_structure(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'function balanceOf(address) view returns (uint256)',
        ]);

        // 测试 multicall 方法存在
        $this->assertTrue(method_exists($contract, 'multicall'));
    }

    /**
     * 测试不存在的函数抛出异常
     */
    public function test_non_existent_function_throws_exception(): void
    {
        $contract = new Contract('0x1234567890123456789012345678901234567890', [
            'function name() view returns (string)',
        ]);

        $this->assertNull($contract->getFunction('nonExistent'));

        $this->expectException(\InvalidArgumentException::class);
        $contract->nonExistent;
    }
}
