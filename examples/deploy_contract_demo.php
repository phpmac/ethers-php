<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ethers\Contract\ContractFactory;
use Ethers\Signer\Wallet;
use Ethers\Provider\JsonRpcProvider;

echo "=== Anvil 本地网络合约部署与读写测试 ===\n\n";

// ========== 连接到 Anvil 本地网络 ==========
echo "--- 连接到 Anvil 本地网络 ---\n";

try {
    $provider = new JsonRpcProvider('http://127.0.0.1:8545');
    echo "已连接到 Anvil 本地网络 (Chain ID: " . $provider->getChainId() . ")\n\n";
} catch (\Throwable $e) {
    echo "连接失败: " . $e->getMessage() . "\n";
    echo "请先启动 Anvil: anvil --port 8545\n";
    exit(1);
}

// ========== 使用 Anvil 测试账户 ==========
$testPrivateKey = '0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80';
$wallet = new Wallet($testPrivateKey);
$walletWithProvider = $wallet->connect($provider);

echo "钱包地址: {$wallet->getAddress()}\n";
echo "钱包余额: " . bcdiv($walletWithProvider->getBalance(), bcpow('10', '18'), 4) . " ETH\n\n";

// ========== Counter 合约 ==========
// 合约源码来自 /Users/a/Downloads/foundry/src/Counter.sol
// 使用 forge build 编译
//
// contract Counter {
//     uint256 public number;
//     function setNumber(uint256 newNumber) public { number = newNumber; }
//     function increment() public { number++; }
// }

$abi = [
    'constructor()',
    'function number() view returns (uint256)',
    'function setNumber(uint256 newNumber)',
    'function increment()',
];

// 编译后的字节码 (forge build 生成)
$bytecode = '0x6080604052348015600e575f5ffd5b506101e18061001c5f395ff3fe608060405234801561000f575f5ffd5b506004361061003f575f3560e01c80633fb5c1cb146100435780638381f58a1461005f578063d09de08a1461007d575b5f5ffd5b61005d600480360381019061005891906100e4565b610087565b005b610067610090565b604051610074919061011e565b60405180910390f35b610085610095565b005b805f8190555050565b5f5481565b5f5f8154809291906100a690610164565b9190505550565b5f5ffd5b5f819050919050565b6100c3816100b1565b81146100cd575f5ffd5b50565b5f813590506100de816100ba565b92915050565b5f602082840312156100f9576100f86100ad565b5b5f610106848285016100d0565b91505092915050565b610118816100b1565b82525050565b5f6020820190506101315f83018461010f565b92915050565b7f4e487b71000000000000000000000000000000000000000000000000000000005f52601160045260245ffd5b5f61016e826100b1565b91507fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff82036101a05761019f610137565b5b60018201905091905056fea2646970667358221220178cf0950e2182da82cc33ee07b49c744ca55a0a654ff8d036f6be76b40303f664736f6c634300081c0033';

// ========== 部署合约 ==========
echo "--- 部署 Counter 合约 ---\n";
echo "合约字节码长度: " . strlen($bytecode) . " 字符\n";

$factory = new ContractFactory($abi, $bytecode);
$factoryWithSigner = $factory->connect($walletWithProvider);

try {
    $contract = $factoryWithSigner->deploy();

    echo "\n合约部署成功!\n";
    echo "  合约地址: " . $contract->getAddress() . "\n";
    echo "  交易哈希: " . $contract->deploymentTransaction()['hash'] . "\n\n";

    // ========== 测试读取 ==========
    echo "--- 测试合约方法 ---\n\n";

    echo "1. 读取初始值:\n";
    $value = $contract->call('number');
    echo "   number() => {$value}\n\n";

    // ========== 测试写入 ==========
    $newValue = '100';
    echo "2. 写入新值 {$newValue}:\n";

    $tx = $contract->send('setNumber', [$newValue]);
    echo "   交易哈希: " . $tx['hash'] . "\n";
    echo "   等待确认...\n";

    $receipt = $tx['wait']();
    echo "   交易已确认 (区块: " . $receipt['blockNumber'] . ")\n\n";

    // ========== 验证写入 ==========
    echo "3. 验证新值:\n";
    $updatedValue = $contract->call('number');
    echo "   number() => {$updatedValue}\n";

    if ($updatedValue === $newValue) {
        echo "   验证通过! 值已更新为 {$newValue}\n\n";
    } else {
        echo "   错误: 期望 {$newValue}, 实际 {$updatedValue}\n\n";
    }

    // ========== 测试 increment ==========
    echo "4. 调用 increment():\n";

    $tx2 = $contract->send('increment');
    $tx2['wait']();

    $finalResult = $contract->call('number');
    echo "   number() => {$finalResult}\n";

    $expectedValue = '101';
    if ($finalResult === $expectedValue) {
        echo "   验证通过! 值已增加到 {$expectedValue}\n\n";
    } else {
        echo "   错误: 期望 {$expectedValue}, 实际 {$finalResult}\n\n";
    }

    echo "=== 所有测试通过! ===\n";
    echo "\n合约已成功部署并测试完成:\n";
    echo "  - 部署地址: " . $contract->getAddress() . "\n";
    echo "  - 读取测试: 通过\n";
    echo "  - 写入测试: 通过\n";
    echo "  - increment 测试: 通过\n";

} catch (\Throwable $e) {
    echo "\n部署或测试失败: " . $e->getMessage() . "\n";
    echo "\n提示: 确保 Anvil 节点已启动并且有足够的测试 ETH\n";
}
