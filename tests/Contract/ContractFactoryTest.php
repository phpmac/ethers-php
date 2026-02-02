<?php

declare(strict_types=1);

namespace Tests\Ethers\Contract;

use Ethers\Contract\Contract;
use Ethers\Contract\ContractFactory;
use PHPUnit\Framework\TestCase;

/**
 * ContractFactory 测试
 */
class ContractFactoryTest extends TestCase
{
    /**
     * 测试创建 ContractFactory
     */
    public function test_create(): void
    {
        $abi = [
            'constructor(string name, string symbol)',
            'function name() view returns (string)',
            'function symbol() view returns (string)',
        ];
        $bytecode = '0x608060405234801561001057600080fd5b50';

        $factory = new ContractFactory($abi, $bytecode);

        $this->assertStringStartsWith('0x', $factory->getBytecode());
        $this->assertNotNull($factory->getInterface());
    }

    /**
     * 测试 getBytecode
     */
    public function test_get_bytecode(): void
    {
        $bytecode = '608060405234801561001057600080fd5b50';
        $factory = new ContractFactory(['function name() view returns (string)'], $bytecode);

        // 应该添加 0x 前缀
        $this->assertEquals('0x'.$bytecode, $factory->getBytecode());
    }

    /**
     * 测试 getInterface
     */
    public function test_get_interface(): void
    {
        $abi = [
            'constructor(string name)',
            'function name() view returns (string)',
        ];
        $factory = new ContractFactory($abi, '0x00');

        $interface = $factory->getInterface();
        $this->assertNotNull($interface->getConstructor());
        $this->assertNotNull($interface->getFunction('name'));
    }

    /**
     * 测试 getDeployTransaction
     */
    public function test_get_deploy_transaction(): void
    {
        $abi = [
            'constructor(string name, string symbol)',
        ];
        $bytecode = '0x608060405234801561001057600080fd5b50';

        $factory = new ContractFactory($abi, $bytecode);
        $deployData = $factory->getDeployTransaction('Test Token', 'TEST');

        $this->assertStringStartsWith('0x', $deployData);
        // 部署数据应该以 bytecode 开头
        $this->assertStringStartsWith($bytecode, $deployData);
        // 部署数据应该比 bytecode 长 (包含编码的构造函数参数)
        $this->assertGreaterThan(strlen($bytecode), strlen($deployData));
    }

    /**
     * 测试无构造函数参数的 getDeployTransaction
     */
    public function test_get_deploy_transaction_without_args(): void
    {
        $bytecode = '0x608060405234801561001057600080fd5b50';
        $factory = new ContractFactory(['function name() view returns (string)'], $bytecode);

        $deployData = $factory->getDeployTransaction();
        $this->assertEquals($bytecode, $deployData);
    }

    /**
     * 测试 attach
     */
    public function test_attach(): void
    {
        $abi = ['function name() view returns (string)'];
        $bytecode = '0x608060405234801561001057600080fd5b50';

        $factory = new ContractFactory($abi, $bytecode);
        $contract = $factory->attach('0x1234567890123456789012345678901234567890');

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals('0x1234567890123456789012345678901234567890', $contract->target);
    }

    /**
     * 测试没有 Signer 时 deploy 抛出异常
     */
    public function test_deploy_without_signer_throws_exception(): void
    {
        $factory = new ContractFactory(['function name() view returns (string)'], '0x00');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('需要 Signer 才能部署合约');

        $factory->deploy();
    }

    /**
     * 测试 getRunner 没有 Signer 时返回 null
     */
    public function test_get_runner_without_signer(): void
    {
        $factory = new ContractFactory(['function name() view returns (string)'], '0x00');
        $this->assertNull($factory->getRunner());
    }

    /**
     * 测试构造函数参数数量不匹配抛出异常
     */
    public function test_deploy_transaction_with_wrong_args_count(): void
    {
        $abi = ['constructor(string name, string symbol)'];
        $factory = new ContractFactory($abi, '0x00');

        $this->expectException(\InvalidArgumentException::class);

        // 只传一个参数, 但构造函数需要两个
        $factory->getDeployTransaction('TestToken');
    }
}
