<?php

declare(strict_types=1);

namespace Ethers\Tests\Contract;

use Ethers\Contract\ContractFactory;
use Ethers\Contract\Contract;
use Ethers\Signer\Wallet;
use PHPUnit\Framework\TestCase;

class ContractFactoryTest extends TestCase
{
    private string $abi = '[{"inputs":[{"internalType":"uint256","name":"initialValue","type":"uint256"}],"stateMutability":"nonpayable","type":"constructor"},{"inputs":[],"name":"retrieve","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"uint256","name":"value","type":"uint256"}],"name":"store","outputs":[],"stateMutability":"nonpayable","type":"function"}]';

    private string $bytecode = '0x608060405234801561001057600080fd5b5060c68061001f6000396000f3fe6080604052348015600f57600080fd5b506004361060325760003560e01c80632e64cec11460375780636057361d14604f575b600080fd5b603d606b565b604051604891906090565b60405180910390f35b606960048036038101906065919060b9565b6071565b005b60008054905090565b6000819050919050565b608a816079565b82525050565b600060208201905060a360008301846083565b92915050565b60006020828403121560bb5760ba6074565b5b600060c784828501607d565b9150509291505056fea2646970667358221220c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a47064736f6c63430008130033';

    public function testCreateFactory(): void
    {
        $factory = new ContractFactory($this->abi, $this->bytecode);

        $this->assertInstanceOf(ContractFactory::class, $factory);
        $this->assertEquals($this->bytecode, $factory->getBytecode());
    }

    public function testGetInterface(): void
    {
        $factory = new ContractFactory($this->abi, $this->bytecode);

        $interface = $factory->getInterface();
        $this->assertNotNull($interface);

        // 检查是否能获取函数
        $retrieveFunc = $interface->getFunction('retrieve');
        $this->assertNotNull($retrieveFunc);
    }

    public function testGetDeployTransaction(): void
    {
        $factory = new ContractFactory($this->abi, $this->bytecode);

        $initialValue = '100';
        $deployData = $factory->getDeployTransaction($initialValue);

        // 验证部署数据包含字节码
        $this->assertStringStartsWith($this->bytecode, $deployData);

        // 验证长度正确(字节码 + 构造参数)
        $this->assertGreaterThan(strlen($this->bytecode), strlen($deployData));
    }

    public function testGetDeployTransactionWithoutConstructor(): void
    {
        // 没有构造函数的 ABI
        $simpleAbi = '[{"inputs":[],"name":"retrieve","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"}]';
        $factory = new ContractFactory($simpleAbi, $this->bytecode);

        $deployData = $factory->getDeployTransaction();

        // 没有构造参数, 应该等于字节码
        $this->assertEquals($this->bytecode, $deployData);
    }

    public function testAttach(): void
    {
        $factory = new ContractFactory($this->abi, $this->bytecode);
        $wallet = Wallet::createRandom();
        $factoryWithSigner = $factory->connect($wallet);

        $address = '0x1234567890123456789012345678901234567890';
        $contract = $factoryWithSigner->attach($address);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals(strtolower($address), $contract->getAddress());
    }

    public function testConnect(): void
    {
        $factory = new ContractFactory($this->abi, $this->bytecode);
        $wallet = Wallet::createRandom();

        $connectedFactory = $factory->connect($wallet);

        $this->assertInstanceOf(ContractFactory::class, $connectedFactory);
        $this->assertSame($wallet, $connectedFactory->getRunner());
    }

    public function testDeployWithoutRunner(): void
    {
        $factory = new ContractFactory($this->abi, $this->bytecode);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('需要 Signer 才能部署合约');

        $factory->deploy('100');
    }

    public function testGetDeployTransactionWithWrongArgumentCount(): void
    {
        $factory = new ContractFactory($this->abi, $this->bytecode);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('构造函数需要 1 个参数, 但提供了 2 个');

        $factory->getDeployTransaction('100', '200');
    }
}
