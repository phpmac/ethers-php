# ethers-php

PHP SDK for Ethereum, inspired by ethers.js v6

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)]()
[![License](https://img.shields.io/badge/license-MIT-green)]()

[中文文档](README_CN.md)

## Features

- Human-readable ABI support (fully compatible with ethers.js v6)
- Complete wallet functionality (creation, signing, sending transactions)
- Contract interaction (calling, deployment, event listening)
- Utility functions (unit conversion, address validation, hashing)

## Installation

```bash
composer require phpmac/ethers-php
```

## Quick Start

### Provider

```php
use Ethers\Ethers;
use Ethers\Provider\JsonRpcProvider;

// Create Provider
$provider = new JsonRpcProvider('https://mainnet.infura.io/v3/YOUR_KEY');

// Or use static method
$provider = Ethers::getDefaultProvider('https://mainnet.infura.io/v3/YOUR_KEY');

// Get network info
$network = $provider->getNetwork();
echo "Chain ID: " . $network['chainId'];  // 1
echo "Name: " . $network['name'];         // mainnet

// Get current block number
$blockNumber = $provider->getBlockNumber();

// Get account balance
$balance = $provider->getBalance('0x...');
echo Ethers::formatEther($balance) . " ETH";

// Get gas price
$gasPrice = $provider->getGasPrice();

// Get fee data (EIP-1559)
$feeData = $provider->getFeeData();
```

### Wallet

```php
use Ethers\Signer\Wallet;

// Create wallet from private key
$wallet = new Wallet('0x...');

// Connect to Provider
$wallet = $wallet->connect($provider);

// Get address
$address = $wallet->getAddress();

// Get balance
$balance = $wallet->getBalance();

// Get nonce
$nonce = $wallet->getNonce();

// Sign message
$signature = $wallet->signMessage('Hello World');

// Send transaction
$response = $wallet->sendTransaction([
    'to' => '0x...',
    'value' => Ethers::parseEther('0.1'),
]);

// Wait for confirmation
$receipt = $response['wait'](1);  // wait for 1 confirmation
```

### Contract

Supports two ABI formats:

#### 1. Human-readable ABI (recommended, same as ethers.js)

```php
use Ethers\Ethers;

$provider = Ethers::getDefaultProvider('https://mainnet.infura.io/v3/YOUR_KEY');
$contractAddress = '0x...';

// Human-readable ABI - same syntax as ethers.js
$abi = [
    'function name() view returns (string)',
    'function symbol() view returns (string)',
    'function decimals() view returns (uint8)',
    'function balanceOf(address owner) view returns (uint256)',
    'function transfer(address to, uint256 amount) returns (bool)',
    'event Transfer(address indexed from, address indexed to, uint256 value)',
];

$contract = Ethers::contract($contractAddress, $abi, $provider);

// Call read-only methods - same as ethers.js
$name = $contract->name();
$symbol = $contract->symbol();
$balance = $contract->balanceOf($userAddress);

echo "$name ($symbol): $balance";
```

#### 2. JSON ABI format

```php
use Ethers\Contract\Contract;

// Standard JSON ABI
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

#### Write operations

```php
// Connect Wallet for write operations
$wallet = Ethers::wallet($privateKey, $provider);
$contract = Ethers::contract($tokenAddress, $abi, $wallet);

// Send transaction - same as ethers.js
$response = $contract->transfer($toAddress, Ethers::parseUnits('100', 18));
$receipt = $response['wait']();

echo "Tx Hash: " . $response['hash'];

// Estimate gas
$gas = $contract->estimateGas('transfer', [$toAddress, Ethers::parseUnits('100', 18)]);

// Static call
$result = $contract->staticCall('transfer', [$toAddress, Ethers::parseUnits('100', 18)]);
```

#### ContractFunction style calls

```php
// Get function object - similar to ethers.js contract.transfer
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

#### IDE Support (Optional)

Contract uses PHP's `__call` magic method for dynamic function calls. This may cause IDE warnings like "Method not defined".

**Solution 1: Use `call()` method**

```php
$name = $contract->call('name');
$balance = $contract->call('balanceOf', [$address]);
```

**Solution 2: Create a typed subclass with PHPDoc**

```php
/**
 * @method string name()
 * @method string symbol()
 * @method string balanceOf(string $owner)
 * @method array transfer(string $to, string $amount)
 */
class TokenContract extends Contract {}

$contract = new TokenContract($address, $abi, $provider);
$name = $contract->name(); // IDE recognizes with full type hints
```

See [CLAUDE.md](CLAUDE.md) for more details.

### Deploy Contract (ContractFactory)

```php
use Ethers\Ethers;
use Ethers\Contract\ContractFactory;

// Human-readable ABI
$abi = [
    'constructor(string name, string symbol)',
    'function name() view returns (string)',
    'function symbol() view returns (string)',
    'function totalSupply() view returns (uint256)',
];

// Contract bytecode (from compiler)
$bytecode = '0x608060405234801561001057600080fd5b50...';

// Create Factory
$factory = Ethers::contractFactory($abi, $bytecode, $wallet);

// Or instantiate directly
$factory = new ContractFactory($abi, $bytecode, $wallet);

// Deploy contract - pass constructor arguments
$contract = $factory->deploy('My Token', 'MTK');

// Wait for deployment
$contract->waitForDeployment();

echo "Deployed to: " . $contract->target;

// Get deployment transaction
$deployTx = $contract->deploymentTransaction();
echo "Tx Hash: " . $deployTx['hash'];

// Call contract methods
$name = $contract->name();  // "My Token"
```

### Parse ABI (Interface)

```php
use Ethers\Ethers;
use Ethers\Contract\Interface_;

// Create Interface from human-readable format
$interface = Ethers::parseAbi([
    'function transfer(address to, uint256 amount) returns (bool)',
    'event Transfer(address indexed from, address indexed to, uint256 value)',
]);

// Or instantiate directly
$interface = new Interface_([
    'function transfer(address to, uint256 amount) returns (bool)',
]);

// Encode function call
$data = $interface->encodeFunctionData('transfer', [$to, $amount]);

// Decode function call
$args = $interface->decodeFunctionData('transfer', $data);

// Get function selector
$func = $interface->getFunction('transfer');
echo $func['selector'];  // 0xa9059cbb

// Format to human-readable
$fragments = $interface->format('minimal');
```

### Utility Functions

```php
use Ethers\Ethers;

// Unit conversion
$wei = Ethers::parseEther('1.5');         // "1500000000000000000"
$ether = Ethers::formatEther($wei);       // "1.5"

$units = Ethers::parseUnits('100', 6);    // USDT 6 decimals
$formatted = Ethers::formatUnits($units, 6);

// Hash
$hash = Ethers::keccak256('Hello');

// Function selector
$selector = Ethers::id('transfer(address,uint256)');  // "0xa9059cbb"

// Address validation
$isValid = Ethers::isAddress('0x...');
$checksumAddress = Ethers::getAddress('0x...');

// Constants
$zero = Ethers::zeroAddress();
$zeroHash = Ethers::zeroHash();
```

## API Reference

### JsonRpcProvider

| Method | Description |
|--------|-------------|
| `getChainId()` | Get chain ID |
| `getNetwork()` | Get network info |
| `getBlockNumber()` | Get current block number |
| `getBalance($address)` | Get account balance |
| `getTransactionCount($address)` | Get transaction count (nonce) |
| `getGasPrice()` | Get gas price |
| `getFeeData()` | Get fee data (EIP-1559) |
| `estimateGas($tx)` | Estimate gas |
| `call($tx)` | Read-only call |
| `sendRawTransaction($signedTx)` | Send signed transaction |
| `getTransaction($hash)` | Get transaction info |
| `getTransactionReceipt($hash)` | Get transaction receipt |
| `waitForTransaction($hash)` | Wait for transaction confirmation |
| `getBlock($blockHashOrNumber)` | Get block info |
| `getLogs($filter)` | Get event logs |

### Wallet

| Method | Description |
|--------|-------------|
| `getAddress()` | Get address |
| `getPrivateKey()` | Get private key |
| `connect($provider)` | Connect to Provider |
| `getBalance()` | Get balance |
| `getNonce()` | Get nonce |
| `signMessage($message)` | Sign message |
| `signTransaction($tx)` | Sign transaction |
| `sendTransaction($tx)` | Send transaction |
| `createRandom()` | Create random wallet |

### Contract

| Method | Description |
|--------|-------------|
| `call($method, $args)` | Read-only call |
| `send($method, $args)` | Send transaction |
| `staticCall($method, $args)` | Static call |
| `estimateGas($method, $args)` | Estimate gas |
| `encodeFunction($method, $args)` | Encode function call |
| `queryFilter($eventName, $filter)` | Query event logs |

## Comparison with ethers.js v6

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

### ABI Format Comparison

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
// ethers-php - exactly the same syntax
$abi = [
    'function name() view returns (string)',
    'function transfer(address to, uint256 amount) returns (bool)',
    'event Transfer(address indexed from, address indexed to, uint256 value)',
];
$contract = new Contract($address, $abi, $provider);
```
