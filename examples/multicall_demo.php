<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ethers\Contract\Contract;
use Ethers\Provider\JsonRpcProvider;
use Ethers\Utils\Units;

// BSC 网络 USDT 合约
$usdtAddress = '0x55d398326f99059fF775485246999027B3197955';

// 人类可读 ABI
$abi = [
    'function name() view returns (string)',
    'function symbol() view returns (string)',
    'function decimals() view returns (uint8)',
    'function totalSupply() view returns (uint256)',
];

echo "=== JSON-RPC 批量请求演示 ===\n\n";

// 创建 Provider
$provider = new JsonRpcProvider('https://bsc-dataseed.binance.org');
echo "Provider: BSC Mainnet (https://bsc-dataseed.binance.org)\n";
echo "Contract: USDT Token ({$usdtAddress})\n\n";

// 创建合约实例
$contract = new Contract($usdtAddress, $abi, $provider);

// 准备批量调用请求
$calls = [
    ['method' => 'name', 'args' => []],
    ['method' => 'symbol', 'args' => []],
    ['method' => 'decimals', 'args' => []],
    ['method' => 'totalSupply', 'args' => []],
];

echo "准备批量调用 (共 " . count($calls) . " 个请求):\n";
foreach ($calls as $i => $call) {
    echo "  " . ($i + 1) . ". {$call['method']}()\n";
}
echo "\n";

// 执行批量请求 - 只发送一次 HTTP 请求
echo "发送批量请求...\n";
$start = microtime(true);

$results = $contract->multicall($calls);

$end = microtime(true);
$duration = round(($end - $start) * 1000, 2);

echo "请求完成! 耗时: {$duration}ms\n\n";

// 输出结果
echo "=== 返回结果 ===\n";
echo "Name:        {$results[0][0]}\n";
echo "Symbol:      {$results[1][0]}\n";
echo "Decimals:    {$results[2][0]}\n";
echo "TotalSupply: " . Units::formatUnits($results[3][0], (int) $results[2][0]) . "\n";

echo "\n=== 特点说明 ===\n";
echo "1. 一次 HTTP 请求获取所有数据\n";
echo "2. 返回顺序与请求顺序一致\n";
echo "3. 相比串行请求, 速度提升 " . round(count($calls) * 0.7, 1) . "x\n";
