# Contract IDE 支持

帮助用户解决 ethers-php Contract 类的 IDE 警告问题.

## 问题

`Contract` 类使用 PHP 的 `__call` 魔术方法动态处理智能合约函数调用.这会导致 IDE (PHPStorm/VSCode) 显示警告:

```
Method Contract::lastOrderId() is not defined
```

## 解决方案

### 方案 1: 使用 `call()` 方法 (最简单,无 IDE 警告)

```php
$lastOrderId = $contract->call('lastOrderId');
$lastProcessedOrderId = $contract->call('lastProcessedOrderId');
```

### 方案 2: 使用 PHPDoc `@method` 注解创建类型子类 (推荐)

创建继承 `Contract` 的类,添加 PHPDoc 注解:

```php
<?php

use Ethers\Contract\Contract;

/**
 * @method string lastOrderId()
 * @method string lastProcessedOrderId()
 * @method array processOrders(string $count, array $overrides = [])
 * @method array getOrder(string $orderId)
 * @method bool isProcessed(string $orderId)
 */
class StakingContract extends Contract
{
}
```

使用:
```php
$contract = new StakingContract($address, $abi, $wallet);
$lastOrderId = $contract->lastOrderId(); // IDE 识别并提供完整类型提示
```

### 方案 3: 使用显式 `getFunction()` 方法

```php
$lastOrderId = $contract->getFunction('lastOrderId')->staticCall([]);
```

## 何时使用哪种方案

- **使用 `call()`** - 需要快速修复或处理未知/动态 ABI 时
- **使用带 `@method` 的子类** - 处理已知合约且需要完整 IDE 支持时
- **使用 `getFunction()`** - 需要对调用进行细粒度控制时

## 返回类型参考

| Solidity 类型 | PHP 返回类型 | 示例 |
|--------------|-------------|------|
| uint256/int256 | string (BigInt) | `@method string balanceOf()` |
| address | string | `@method string getOwner()` |
| bool | bool | `@method bool isActive()` |
| bytes/string | string | `@method string name()` |
| struct/tuple | array | `@method array getUserInfo()` |
| 交易 | array | `@method array transfer(string $to, string $amount)` |
