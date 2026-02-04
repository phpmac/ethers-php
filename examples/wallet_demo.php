<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ethers\Signer\Wallet;
use Ethers\Provider\JsonRpcProvider;

echo "=== 钱包账户操作演示 ===\n\n";

// ========== 示例1: 创建新账户 ==========
echo "--- 示例1: 创建随机账户 ---\n";

$wallet1 = Wallet::createRandom();

echo "新账户创建成功!\n";
echo "  地址:    {$wallet1->getAddress()}\n";
echo "  私钥:    {$wallet1->getPrivateKey()}\n";
echo "\n";

// ========== 示例2: 通过私钥恢复账户 ==========
echo "--- 示例2: 通过私钥恢复账户 ---\n";

// 使用示例1生成的私钥(实际使用时可以用你自己的私钥)
$privateKey = $wallet1->getPrivateKey();

// 从私钥恢复钱包
$wallet2 = new Wallet($privateKey);

echo "从私钥恢复账户成功!\n";
echo "  原地址:  {$wallet1->getAddress()}\n";
echo "  恢复地址: {$wallet2->getAddress()}\n";
echo "  地址匹配: " . ($wallet1->getAddress() === $wallet2->getAddress() ? '是' : '否') . "\n";
echo "\n";

// ========== 示例3: 创建多个账户 ==========
echo "--- 示例3: 批量创建账户 ---\n";

$accounts = [];
for ($i = 0; $i < 3; $i++) {
    $wallet = Wallet::createRandom();
    $accounts[] = [
        'address' => $wallet->getAddress(),
        'privateKey' => $wallet->getPrivateKey(),
    ];
    echo "  账户 " . ($i + 1) . ": {$wallet->getAddress()}\n";
}
echo "\n";

// ========== 示例4: 连接 Provider 查询余额 ==========
echo "--- 示例4: 连接 Provider 查询余额 ---\n";

// 使用 BSC 测试网(不发送交易, 只是查询)
try {
    $provider = new JsonRpcProvider('https://bsc-testnet.publicnode.com');
    $walletWithProvider = $wallet1->connect($provider);

    echo "已连接到 BSC 测试网\n";
    echo "  地址: {$walletWithProvider->getAddress()}\n";

    $balance = $walletWithProvider->getBalance();
    echo "  余额: {$balance} wei\n";
} catch (\Throwable $e) {
    echo "查询失败: " . $e->getMessage() . "\n";
}
echo "\n";

