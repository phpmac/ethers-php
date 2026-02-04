<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ethers\Contract\Contract;
use Ethers\Signer\Wallet;
use Ethers\Provider\JsonRpcProvider;

echo "=== 代理合约升级演示(UUPS 模式) ===\n\n";

// ========== 示例: UUPS 代理合约交互 ==========
// UUPS (Universal Upgradeable Proxy Standard) 是一种代理合约模式
// - 代理合约存储状态, 委托调用逻辑合约
// - 升级时更新逻辑合约地址
// - 通常由 ProxyAdmin 合约管理升级

echo "--- UUPS 代理合约结构 ---\n";
echo "1. 代理合约(Proxy): 存储状态, 委托调用到实现合约\n";
echo "2. 实现合约(Implementation): 包含业务逻辑\n";
echo "3. ProxyAdmin: 管理权限, 执行升级\n\n";

// ========== 典型 UUPS 代理 ABI ==========
$proxyAbi = [
    // ERC1967 标准函数
    'function implementation() view returns (address)',
    'function upgradeTo(address newImplementation)',
    'function upgradeToAndCall(address newImplementation, bytes data)',
    // 代理管理员函数
    'function admin() view returns (address)',
    'function changeAdmin(address newAdmin)',
];

$erc1967Abi = [
    'event Upgraded(address indexed implementation)',
    'event AdminChanged(address previousAdmin, address newAdmin)',
];

echo "--- 代理合约 ABI 示例 ---\n";
foreach ($proxyAbi as $item) {
    echo "  - {$item}\n";
}
echo "\n";

// ========== 交互示例 ==========
echo "--- 与代理合约交互示例 ---\n\n";

echo "// 1. 连接到代理合约\n";
echo "\$proxy = new Contract(\n";
echo "    '0x代理合约地址',\n";
echo "    \$implementationAbi, // 使用实现合约的 ABI\n";
echo "    \$signer\n";
echo ");\n\n";

echo "// 2. 调用实现合约的方法(自动委托调用)\n";
echo "\$value = \$proxy->call('getValue'); // 读取状态\n";
echo "\$tx = \$proxy->send('setValue', [100]); // 写入状态\n\n";

echo "// 3. 检查当前实现地址\n";
echo "\$proxyContract = new Contract(\n";
echo "    '0x代理合约地址',\n";
echo "    ['function implementation() view returns (address)'],\n";
echo "    \$provider\n";
echo ");\n";
echo "\$implAddress = \$proxyContract->call('implementation');\n";
echo "echo \"当前实现合约: \$implAddress\n\n\";\n\n";

echo "// 4. 升级合约(需要 ProxyAdmin 权限)\n";
echo "\$adminContract = new Contract(\n";
echo "    '0xProxyAdmin地址',\n";
echo "    ['function upgrade(address proxy, address implementation)'],\n";
echo "    \$adminSigner\n";
echo ");\n";
echo "\$tx = \$adminContract->send('upgrade', [\n";
echo "    '0x代理合约地址',\n";
echo "    '0x新实现合约地址'\n";
echo "]);\n";
echo "\$tx['wait'](); // 等待升级完成\n\n";

// ========== 代码示例 ==========
echo "--- 完整代码示例 ---\n";
echo <<<'CODE'
use Ethers\Contract\Contract;
use Ethers\Signer\Wallet;
use Ethers\Provider\JsonRpcProvider;

// 配置
$proxyAddress = '0x...';      // 代理合约地址
$implAddress = '0x...';       // 当前实现地址
$newImplAddress = '0x...';    // 新实现地址(升级后)
$adminAddress = '0x...';      // ProxyAdmin 地址
$adminPrivateKey = '0x...';   // 管理员私钥

// 连接
$provider = new JsonRpcProvider('https://bsc-testnet.publicnode.com');
$adminWallet = new Wallet($adminPrivateKey, $provider);

// 1. 与代理合约交互(使用实现合约 ABI)
$erc20Abi = [
    'function name() view returns (string)',
    'function balanceOf(address) view returns (uint256)',
    'function transfer(address, uint256) returns (bool)',
];

$token = new Contract($proxyAddress, $erc20Abi, $adminWallet);
$name = $token->call('name');
echo "Token 名称: $name\n";

// 2. 查询当前实现地址
$proxyAdminAbi = ['function implementation() view returns (address)'];
$proxy = new Contract($proxyAddress, $proxyAdminAbi, $provider);
$currentImpl = $proxy->call('implementation');
echo "当前实现: $currentImpl\n";

// 3. 升级合约(通过 ProxyAdmin)
$adminAbi = [
    'function upgrade(address proxy, address implementation)',
    'function owner() view returns (address)',
];

$admin = new Contract($adminAddress, $adminAbi, $adminWallet);

// 检查权限
$owner = $admin->call('owner');
echo "ProxyAdmin 所有者: $owner\n";

// 执行升级
$tx = $admin->send('upgrade', [$proxyAddress, $newImplAddress]);
echo "升级交易: {$tx['hash']}\n";
$receipt = $tx['wait']();
echo "升级完成, 区块: {$receipt['blockNumber']}\n";

// 4. 验证升级
$newImpl = $proxy->call('implementation');
echo "新实现地址: $newImpl\n";

CODE;

echo "\n=== 注意事项 ===\n";
echo "1. 代理合约本身没有逻辑, 只是委托调用\n";
echo "2. 升级操作需要 ProxyAdmin 权限\n";
echo "3. 升级前应在测试网充分测试\n";
echo "4. 考虑使用多签钱包管理升级权限\n";
echo "5. 升级后验证存储槽位是否兼容\n";
