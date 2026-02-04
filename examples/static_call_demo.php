<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ethers\Contract\Contract;
use Ethers\Provider\JsonRpcProvider;

echo "=== 模拟交易调用 (Static Call) 演示 ===\n\n";

// 使用 BSC 网络
$provider = new JsonRpcProvider('https://bsc-dataseed.binance.org');
echo "Provider: BSC Mainnet\n\n";

// 示例1: USDT 合约 - 模拟转账 (会失败, 因为没有余额)
echo "--- 示例1: 模拟 USDT 转账 (预期失败) ---\n";

$usdtAddress = '0x55d398326f99059fF775485246999027B3197955';
$usdtAbi = [
    'function name() view returns (string)',
    'function symbol() view returns (string)',
    'function decimals() view returns (uint8)',
    'function transfer(address to, uint256 amount) returns (bool)',
    'function balanceOf(address account) view returns (uint256)',
];

$usdt = new Contract($usdtAddress, $usdtAbi, $provider);

// 用一个随机地址测试 (该地址没有 USDT 余额)
$randomAddress = '0x1234567890123456789012345678901234567890';

try {
    // 先查询余额
    $balance = $usdt->call('balanceOf', [$randomAddress]);
    echo "地址 {$randomAddress} 的 USDT 余额: {$balance[0]}\n";

    // 尝试模拟转账 100 USDT
    echo "尝试模拟转账 100 USDT...\n";
    $amount = '100000000000000000000'; // 100 * 10^18

    // 使用 staticCall 模拟转账 (不会真正执行)
    $result = $usdt->staticCall('transfer', ['0xAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $amount]);

    echo "模拟结果: " . ($result[0] ? '成功' : '失败') . "\n";
} catch (\Ethers\Errors\CallExceptionError $e) {
    echo "捕获到合约错误!\n";
    echo "  错误类型: " . $e::class . "\n";
    echo "  错误代码: " . $e->code . "\n";
    echo "  错误信息: " . $e->getMessage() . "\n";

    if ($e->data) {
        echo "  错误数据: " . $e->data . "\n";
    }
} catch (\Throwable $e) {
    echo "其他错误: " . $e->getMessage() . "\n";
}

echo "\n";

// 示例2: 使用错误的数据调用 (方法不存在)
echo "--- 示例2: 调用不存在的方法 (预期失败) ---\n";

try {
    $result = $usdt->staticCall('nonExistentMethod', []);
    echo "结果: " . print_r($result, true) . "\n";
} catch (\Ethers\Errors\CallExceptionError $e) {
    echo "捕获到合约错误!\n";
    echo "  错误信息: " . $e->getMessage() . "\n";
} catch (\InvalidArgumentException $e) {
    echo "参数错误: " . $e->getMessage() . "\n";
} catch (\Throwable $e) {
    echo "其他错误: " . $e->getMessage() . "\n";
}

echo "\n";

// 示例3: 正确的只读调用 (不会失败)
echo "--- 示例3: 正确的只读调用 (预期成功) ---\n";

try {
    // 使用 call 方法 (staticCall 的别名)
    // 注意: 单返回值直接返回该值, 不是数组
    $name = $usdt->call('name', []);
    $symbol = $usdt->call('symbol', []);
    $decimals = $usdt->call('decimals', []);

    echo "代币名称: {$name}\n";
    echo "代币符号: {$symbol}\n";
    echo "小数位数: {$decimals}\n";
} catch (\Throwable $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n=== 特点说明 ===\n";
echo "1. staticCall 不会发送真实交易, 只模拟执行\n";
echo "2. 可以捕获合约 revert 错误, 避免 gas 浪费\n";
echo "3. 适用于转账前检查、参数验证等场景\n";
echo "4. 错误信息包含 revert 原因, 便于调试\n";
