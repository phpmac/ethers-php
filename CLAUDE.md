# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目说明

**ethers-php** - PHP 版 Ethereum SDK, 参考 [ethers.js v6](https://docs.ethers.org/v6/) 实现.

本项目是 ethers.js v6 的 PHP 移植版本, 提供类似的 API 设计模式用于以太坊区块链交互.

## 常用命令

```bash
# 运行测试
composer test

# 运行指定测试文件
./vendor/bin/phpunit tests/Contract/ContractTest.php
```

## 合约调用与 IDE 支持

### 问题

Contract 类使用 `__call` 魔术方法动态处理合约函数调用,IDE (PHPStorm/VSCode) 无法识别这些动态方法,会显示警告:

```
Method Contract::lastOrderId() is not defined
```

### 解决方案

#### 方案 1: 使用 `call()` 方法 (简单,无 IDE 警告)

```php
$lastOrderId = $contract->call('lastOrderId');
$lastProcessedOrderId = $contract->call('lastProcessedOrderId');
```

#### 方案 2: 使用 PHPDoc `@method` 注解 (推荐,有完整类型提示)

为具体合约创建子类,添加 `@method` 注解:

```php
/**
 * @method string lastOrderId()
 * @method string lastProcessedOrderId()
 * @method array processOrders(string $count, array $overrides = [])
 * @method array getOrder(uint256 $orderId)
 */
class StakingContract extends Contract
{
}

// 使用
$contract = new StakingContract($address, $abi, $wallet);
$lastOrderId = $contract->lastOrderId(); // IDE 正常识别,有类型提示
```

**支持的注解格式:**

- `@method returnType methodName()` - 无参数方法
- `@method returnType methodName(paramType $param)` - 带参数
- `@method returnType methodName(paramType $param, array $overrides = [])` - 支持覆盖参数

**常见返回类型:**

- `string` - 数字类型 (uint256/int256) 返回 BigInt 字符串
- `string` - address 类型返回地址字符串
- `bool` - 布尔类型
- `array` - 交易返回 `['hash' => string, 'wait' => callable]`

#### 方案 3: 使用 `getFunction()` 显式调用

```php
$lastOrderId = $contract->getFunction('lastOrderId')->staticCall([]);
```

## 批量请求 (multicall)

使用 JSON-RPC 2.0 批量请求特性,将多个合约调用合并为**一次 HTTP 请求**.

**参考示例:** `examples/multicall_demo.php`

**关键特点:**

- 一次 HTTP 请求获取多个数据
- 返回顺序与请求顺序一致
- 使用 `Units::formatUnits()` 格式化代币数量

## 版本发布

遵循 [语义化版本](https://semver.org/lang/zh-CN/) 规范:

```bash
# 创建新版本标签
git tag -a v1.0.0 -m "发布 v1.0.0"
git push origin v1.0.0
```

