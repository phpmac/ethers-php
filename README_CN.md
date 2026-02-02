# ethers-php

PHP SDK for Ethereum，inspired by ethers.js v6

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)]()
[![License](https://img.shields.io/badge/license-MIT-green)]()

[English Documentation](README.md)

## 特性

- 支持人类可读 ABI（与 ethers.js v6 完全一致）
- 完整的钱包功能（创建、签名、发送交易）
- 合约交互（调用、部署、事件监听）
- 工具函数（单位转换、地址校验、哈希计算）

## 安装

```bash
composer require phpmac/ethers-php
```

## 快速开始

### Provider

```php
use Ethers\Ethers;
use Ethers\Provider\JsonRpcProvider;

// 创建 Provider
$provider = new JsonRpcProvider('https://mainnet.infura.io/v3/YOUR_KEY');

// 或使用静态方法
$provider = Ethers::getDefaultProvider('https://mainnet.infura.io/v3/YOUR_KEY');

// 获取网络信息
$network = $provider->getNetwork();
echo "Chain ID：" . $network['chainId'];  // 1
echo "Name：" . $network['name'];         // mainnet

// 获取当前区块号
$blockNumber = $provider->getBlockNumber();

// 获取账户余额
$balance = $provider->getBalance('0x...');
echo Ethers::formatEther($balance) . " ETH";

// 获取 Gas 价格
$gasPrice = $provider->getGasPrice();

// 获取费用数据（EIP-1559）
$feeData = $provider->getFeeData();
```

### Wallet

```php
use Ethers\Signer\Wallet;

// 从私钥创建钱包
$wallet = new Wallet('0x...');

// 连接 Provider
$wallet = $wallet->connect($provider);

// 获取地址
$address = $wallet->getAddress();

// 获取余额
$balance = $wallet->getBalance();

// 获取 nonce
$nonce = $wallet->getNonce();

// 签名消息
$signature = $wallet->signMessage('Hello World');

// 发送交易
$response = $wallet->sendTransaction([
    'to' => '0x...',
    'value' => Ethers::parseEther('0.1'),
]);

// 等待确认
$receipt = $response['wait'](1);  // 等待 1 个确认
```

### Contract

支持两种 ABI 格式：

#### 1. 人类可读 ABI（推荐，与 ethers.js 一致）

```php
use Ethers\Ethers;

$provider = Ethers::getDefaultProvider('https://mainnet.infura.io/v3/YOUR_KEY');
$contractAddress = '0x...';

// 人类可读 ABI —— 与 ethers.js 完全一致的写法
$abi = [
    'function name() view returns (string)',
    'function symbol() view returns (string)',
    'function decimals() view returns (uint8)',
    'function balanceOf(address owner) view returns (uint256)',
    'function transfer(address to, uint256 amount) returns (bool)',
    'event Transfer(address indexed from, address indexed to, uint256 value)',
];

$contract = Ethers::contract($contractAddress, $abi, $provider);

// 调用只读方法 —— 与 ethers.js 完全一致
$name = $contract->name();
$symbol = $contract->symbol();
$balance = $contract->balanceOf($userAddress);

echo "$name ($symbol): $balance";
```

#### 2. JSON ABI 格式

```php
use Ethers\Contract\Contract;

// 标准 JSON ABI
$erc20Abi = [
    [
        'type' => 'function',
        'name' => 'balanceOf',
        'inputs' => [['name' => 'account', 'type' => 'address']],
        'outputs' => [['name' => '', 'type' => 'uint256']],
        'stateMutability' => 'view',
    ],
    [
        'type' => 'function',
        'name' => 'transfer',
        'inputs' => [
            ['name' => 'to', 'type' => 'address'],
            ['name' => 'amount', 'type' => 'uint256'],
        ],
        'outputs' => [['name' => '', 'type' => 'bool']],
        'stateMutability' => 'nonpayable',
    ],
];

$contract = new Contract($tokenAddress, $erc20Abi, $provider);
$balance = $contract->balanceOf($userAddress);
```

#### 写入操作

```php
// 连接 Wallet 进行写操作
$wallet = Ethers::wallet($privateKey, $provider);
$contract = Ethers::contract($tokenAddress, $abi, $wallet);

// 发送交易 —— 与 ethers.js 一致
$response = $contract->transfer($toAddress, Ethers::parseUnits('100', 18));
$receipt = $response['wait']();

echo "Tx Hash：" . $response['hash'];

// 估算 Gas
$gas = $contract->estimateGas('transfer', [$toAddress, Ethers::parseUnits('100', 18)]);

// 模拟调用（staticCall）
$result = $contract->staticCall('transfer', [$toAddress, Ethers::parseUnits('100', 18)]);
```

#### ContractFunction 风格调用

```php
// 获取函数对象 —— 类似 ethers.js 的 contract.transfer
$transferFunc = $contract->getFunction('transfer');

// staticCall
$result = $transferFunc->staticCall([$to, $amount]);

// estimateGas
$gas = $transferFunc->estimateGas([$to, $amount]);

// send
$response = $transferFunc->send([$to, $amount]);

// populateTransaction
$tx = $transferFunc->populateTransaction([$to, $amount]);
```

### 部署合约（ContractFactory）

```php
use Ethers\Ethers;
use Ethers\Contract\ContractFactory;

// 人类可读 ABI
$abi = [
    'constructor(string name, string symbol)',
    'function name() view returns (string)',
    'function symbol() view returns (string)',
    'function totalSupply() view returns (uint256)',
];

// 合约字节码（从编译器获取）
$bytecode = '0x608060405234801561001057600080fd5b50...';

// 创建 Factory
$factory = Ethers::contractFactory($abi, $bytecode, $wallet);

// 或直接实例化
$factory = new ContractFactory($abi, $bytecode, $wallet);

// 部署合约 - 传入构造函数参数
$contract = $factory->deploy('My Token', 'MTK');

// 等待部署完成
$contract->waitForDeployment();

echo "Deployed to: " . $contract->target;

// 获取部署交易
$deployTx = $contract->deploymentTransaction();
echo "Tx Hash: " . $deployTx['hash'];

// 调用合约方法
$name = $contract->name();  // "My Token"
```

### 解析 ABI（Interface）

```php
use Ethers\Ethers;
use Ethers\Contract\Interface_;

// 从人类可读格式创建 Interface
$interface = Ethers::parseAbi([
    'function transfer(address to, uint256 amount) returns (bool)',
    'event Transfer(address indexed from, address indexed to, uint256 value)',
]);

// 或直接实例化
$interface = new Interface_([
    'function transfer(address to, uint256 amount) returns (bool)',
]);

// 编码函数调用
$data = $interface->encodeFunctionData('transfer', [$to, $amount]);

// 解码函数调用
$args = $interface->decodeFunctionData('transfer', $data);

// 获取函数选择器
$func = $interface->getFunction('transfer');
echo $func['selector'];  // 0xa9059cbb

// 格式化为人类可读格式
$fragments = $interface->format('minimal');
```

### 工具函数

```php
use Ethers\Ethers;

// 单位转换
$wei = Ethers::parseEther('1.5');         // "1500000000000000000"
$ether = Ethers::formatEther($wei);       // "1.5"

$units = Ethers::parseUnits('100', 6);    // USDT 的 6 位精度
$formatted = Ethers::formatUnits($units, 6);

// 哈希
$hash = Ethers::keccak256('Hello');

// 函数选择器
$selector = Ethers::id('transfer(address,uint256)');  // "0xa9059cbb"

// 地址校验
$isValid = Ethers::isAddress('0x...');
$checksumAddress = Ethers::getAddress('0x...');

// 常量
$zero = Ethers::zeroAddress();
$zeroHash = Ethers::zeroHash();
```

## API 参考

### JsonRpcProvider

| 方法 | 说明 |
|------|------|
| `getChainId()` | 获取链 ID |
| `getNetwork()` | 获取网络信息 |
| `getBlockNumber()` | 获取当前区块号 |
| `getBalance($address)` | 获取账户余额 |
| `getTransactionCount($address)` | 获取交易计数（nonce） |
| `getGasPrice()` | 获取 Gas 价格 |
| `getFeeData()` | 获取费用数据（EIP-1559）|
| `estimateGas($tx)` | 估算 Gas |
| `call($tx)` | 只读调用 |
| `sendRawTransaction($signedTx)` | 发送已签名交易 |
| `getTransaction($hash)` | 获取交易信息 |
| `getTransactionReceipt($hash)` | 获取交易回执 |
| `waitForTransaction($hash)` | 等待交易确认 |
| `getBlock($blockHashOrNumber)` | 获取区块信息 |
| `getLogs($filter)` | 获取事件日志 |

### Wallet

| 方法 | 说明 |
|------|------|
| `getAddress()` | 获取地址 |
| `getPrivateKey()` | 获取私钥 |
| `connect($provider)` | 连接 Provider |
| `getBalance()` | 获取余额 |
| `getNonce()` | 获取 nonce |
| `signMessage($message)` | 签名消息 |
| `signTransaction($tx)` | 签名交易 |
| `sendTransaction($tx)` | 发送交易 |
| `createRandom()` | 创建随机钱包 |

### Contract

| 方法 | 说明 |
|------|------|
| `call($method, $args)` | 只读调用 |
| `send($method, $args)` | 发送交易 |
| `staticCall($method, $args)` | 模拟调用 |
| `estimateGas($method, $args)` | 估算 Gas |
| `encodeFunction($method, $args)` | 编码函数调用 |
| `queryFilter($eventName, $filter)` | 查询事件日志 |

## 与 ethers.js v6 对比

| ethers.js v6 | ethers-php |
|--------------|------------|
| `new ethers.JsonRpcProvider(url)` | `new JsonRpcProvider($url)` |
| `new ethers.Wallet(key, provider)` | `new Wallet($key, $provider)` |
| `new ethers.Contract(addr, abi, runner)` | `new Contract($addr, $abi, $runner)` |
| `new ethers.ContractFactory(abi, bytecode, signer)` | `new ContractFactory($abi, $bytecode, $signer)` |
| `ethers.parseEther('1.0')` | `Ethers::parseEther('1.0')` |
| `ethers.Interface.from(abi)` | `Interface_::from($abi)` |
| `contract.target` | `$contract->target` |
| `contract.balanceOf(addr)` | `$contract->balanceOf($addr)` |
| `contract.transfer.staticCall(to, amount)` | `$contract->transfer->staticCall([$to, $amount])` |
| `contract.transfer.estimateGas(to, amount)` | `$contract->transfer->estimateGas([$to, $amount])` |
| `contract.getFunction('transfer')` | `$contract->getFunction('transfer')` |
| `factory.deploy(arg1, arg2)` | `$factory->deploy($arg1, $arg2)` |
| `contract.waitForDeployment()` | `$contract->waitForDeployment()` |

### ABI 格式对比

```javascript
// ethers.js v6
const abi = [
    "function name() view returns (string)",
    "function transfer(address to, uint256 amount) returns (bool)",
    "event Transfer(address indexed from, address indexed to, uint256 value)",
];
const contract = new ethers.Contract(address, abi, provider);
```

```php
// ethers-php —— 完全一致的写法
$abi = [
    'function name() view returns (string)',
    'function transfer(address to, uint256 amount) returns (bool)',
    'event Transfer(address indexed from, address indexed to, uint256 value)',
];
$contract = new Contract($address, $abi, $provider);
```

