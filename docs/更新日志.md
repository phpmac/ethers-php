# 更新日志

## 2026-02-04

### 新增功能

#### 1. Wallet.cancelTransaction() - 取消交易

取消 pending 状态的交易:

```php
$cancelTx = $wallet->cancelTransaction($pendingTxHash);
$cancelTx['wait']();
```

实现原理: 用相同 nonce 发送 0 ETH 给自己的交易, gas price 提高 10% 覆盖原交易.

#### 2. ErrorFactory - 错误工厂

统一处理 RPC 错误,自动分类异常:

```php
$exception = ErrorFactory::fromRpcError($code, $message, $transaction);
```

错误映射:
- replacement transaction underpriced -> ReplacementUnderpricedError
- nonce too low -> NonceExpiredError
- insufficient funds -> InsufficientFundsError
- 其他 -> ServerError

#### 3. 新增异常类

| 异常类 | 代码 | 说明 |
|-------|------|------|
| ReplacementUnderpricedError | REPLACEMENT_UNDERPRICED | 替换交易 gas 价格过低 |
| TransactionReplacedError | TRANSACTION_REPLACED | 交易被替换 |
| InvalidArgumentError | INVALID_ARGUMENT | 参数无效 |
| CancelledError | CANCELLED | 操作已取消 |
| UnknownError | UNKNOWN_ERROR | 未知错误兜底 |
| BadDataError | BAD_DATA | 数据格式错误 |

### 修复

#### NonceExpiredError 消息格式

改为 ethers.js 风格的友好消息:

```
之前: "RPC Error [-32000]: nonce too low"
现在: "nonce has already been used"
```

#### 错误分类修复

replacement transaction underpriced 现在正确抛出 ReplacementUnderpricedError,
而不是 NonceExpiredError.

#### 4. JSON-RPC 批量请求 (multicall)

Contract::multicall() 现在使用真正的 JSON-RPC 2.0 批量请求, 将多个调用合并为**一次 HTTP 请求**.

测试: tests/Contract/MulticallIntegrationTest.php (使用 BSC USDT 合约)

### 测试

- 共 165 个测试, 383 个断言
