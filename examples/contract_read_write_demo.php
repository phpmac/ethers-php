<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ethers\Contract\Contract;
use Ethers\Provider\JsonRpcProvider;
use Ethers\Utils\Units;

echo "=== 合约读取与写入演示 ===\n\n";

// 使用 BSC 主网上的 USDT 合约 (只读演示)
$usdtAddress = '0x55d398326f99059fF775485246999027B3197955';
$provider = new JsonRpcProvider('https://bsc-dataseed.binance.org');

$abi = [
    'function name() view returns (string)',
    'function symbol() view returns (string)',
    'function decimals() view returns (uint8)',
    'function totalSupply() view returns (uint256)',
    'function balanceOf(address account) view returns (uint256)',
    'function transfer(address to, uint256 amount) returns (bool)',
    'event Transfer(address indexed from, address indexed to, uint256 value)',
];

$contract = new Contract($usdtAddress, $abi, $provider);

echo "合约: USDT ({$usdtAddress})\n";
echo "网络: BSC Mainnet\n\n";

// ========== 读取操作 (Read) ==========
echo "--- 读取操作 (无需 gas, 不修改状态) ---\n\n";

// 1. 基础信息
echo "1. 基础信息:\n";
$name = $contract->call('name');
$symbol = $contract->call('symbol');
$decimals = $contract->call('decimals');

echo "   名称:   {$name}\n";
echo "   符号:   {$symbol}\n";
echo "   精度:   {$decimals}\n\n";

// 2. 总供应量
echo "2. 总供应量:\n";
$totalSupply = $contract->call('totalSupply');
$decimalsInt = (int) $decimals;
$formattedSupply = Units::formatUnits($totalSupply, $decimalsInt);
echo "   原始值: {$totalSupply}\n";
echo "   格式化: {$formattedSupply} {$symbol}\n\n";

// 3. 查询余额
echo "3. 查询余额:\n";
$holderAddress = '0xF977814e90dA44bFA03b6295A0616a897441aceC'; // Binance Hot Wallet
$balance = $contract->call('balanceOf', [$holderAddress]);
$formattedBalance = Units::formatUnits($balance, $decimalsInt);
echo "   地址:   {$holderAddress}\n";
echo "   余额:   {$formattedBalance} {$symbol}\n\n";

// ========== 写入操作 (Write) ==========
echo "--- 写入操作 (需要 gas, 修改状态) ---\n\n";

echo "写入操作需要 Signer(钱包):\n\n";

echo "// 创建带私钥的钱包\n";
echo "use Ethers\Signer\Wallet;\n\n";
echo "\$wallet = new Wallet('0x你的私钥', \$provider);\n";
echo "\$contractWithSigner = \$contract->connect(\$wallet);\n\n";

echo "// 发送交易 - 转账\n";
echo "\$to = '0x接收地址';\n";
echo "\$amount = Units::parseUnits('100', 18); // 100 USDT\n\n";

echo "\$tx = \$contractWithSigner->send('transfer', [\$to, \$amount]);\n";
echo "echo \$tx['hash']; // 交易哈希\n\n";

echo "// 等待确认\n";
echo "\$receipt = \$tx['wait'](1, 60); // 1个确认, 60秒超时\n";
echo "echo \$receipt['status']; // 1 = 成功\n\n";

// ========== 读取 vs 写入对比 ==========
echo "--- 读取 vs 写入对比 ---\n\n";

echo "| 特性     | 读取 (Call)          | 写入 (Send)          |\n";
echo "|----------|---------------------|---------------------|\n";
echo "| 消耗 gas | 否                  | 是                  |\n";
echo "| 修改状态 | 否                  | 是                  |\n";
echo "| 需要签名 | 否                  | 是                  |\n";
echo "| 返回数据 | 返回值              | 交易哈希            |\n";
echo "| 执行速度 | 即时                | 需等待区块确认      |\n";
echo "| 错误处理 | 立即抛出异常        | 可能回滚交易        |\n\n";

// ========== 方法调用方式总结 ==========
echo "--- 方法调用方式 ---\n\n";

echo "1. call() - 只读调用:\n";
echo "   \$value = \$contract->call('balanceOf', [\$address]);\n\n";

echo "2. send() - 写入调用:\n";
echo "   \$tx = \$contract->send('transfer', [\$to, \$amount]);\n\n";

echo "3. staticCall() - 模拟调用 (预测结果):\n";
echo "   try {\n";
echo "       \$result = \$contract->staticCall('transfer', [\$to, \$amount]);\n";
echo "       // 模拟成功, 实际发送可能成功\n";
echo "   } catch (\$e) {\n";
echo "       // 模拟失败, 避免浪费 gas\n";
echo "   }\n\n";

echo "4. estimateGas() - 估算 gas:\n";
echo "   \$gas = \$contract->getFunction('transfer')->estimateGas([\$to, \$amount]);\n";
