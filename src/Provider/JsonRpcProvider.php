<?php

declare(strict_types=1);

namespace Ethers\Provider;

use Ethers\Errors\CallExceptionError;
use Ethers\Errors\InsufficientFundsError;
use Ethers\Errors\NetworkError;
use Ethers\Errors\NonceExpiredError;
use Ethers\Errors\ServerError;
use Ethers\Errors\TimeoutError;
use Ethers\Utils\Hex;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * JsonRpcProvider
 * JSON-RPC 提供者, 用于与以太坊节点通信
 *
 * 参考 ethers.js v6 的 JsonRpcProvider
 */
class JsonRpcProvider
{
    private Client $client;

    private string $url;

    private int $requestId = 1;

    private ?int $chainId = null;

    /**
     * 构造函数
     *
     * @param  string  $url  RPC 节点 URL
     * @param  array  $options  Guzzle 选项
     */
    public function __construct(string $url, array $options = [])
    {
        $this->url = $url;
        $defaultOptions = [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];
        $this->client = new Client(array_merge($defaultOptions, $options));
    }

    /**
     * 发送 JSON-RPC 请求
     *
     * @param  string  $method  RPC 方法名
     * @param  array  $params  参数
     * @param  array  $context  上下文信息 (用于错误处理)
     * @return mixed 返回结果
     *
     * @throws CallExceptionError
     * @throws InsufficientFundsError
     * @throws NonceExpiredError
     * @throws NetworkError
     * @throws ServerError
     * @throws TimeoutError
     */
    public function send(string $method, array $params = [], array $context = []): mixed
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $this->requestId++,
        ];

        try {
            $response = $this->client->post($this->url, [
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['error'])) {
                $error = $body['error'];
                $message = $error['message'] ?? 'Unknown RPC error';
                $code = $error['code'] ?? -1;
                $data = $error['data'] ?? null;

                throw $this->parseRpcError($code, $message, $data, $method, $context);
            }

            return $body['result'] ?? null;
        } catch (ConnectException $e) {
            throw new NetworkError(
                'RPC 连接失败: '.$e->getMessage(),
                'connect',
                ['url' => $this->url]
            );
        } catch (GuzzleException $e) {
            if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout')) {
                throw new TimeoutError(
                    'RPC 请求超时: '.$e->getMessage(),
                    $method,
                    'timeout',
                    ['url' => $this->url]
                );
            }

            throw new NetworkError(
                'RPC 请求失败: '.$e->getMessage(),
                'request',
                ['url' => $this->url]
            );
        }
    }

    /**
     * 发送 JSON-RPC 批量请求 (一次网络请求多个调用)
     *
     * **重要**: 此方法使用 JSON-RPC 2.0 批量请求特性，将多个 RPC 调用合并为一次 HTTP 请求
     *
     * @param  array<int, array{method: string, params: array, context?: array}>  $requests  请求数组
     * @return array<int, mixed> 返回结果数组 (顺序与请求一致)
     *
     * @throws NetworkError
     * @throws ServerError
     * @throws TimeoutError
     */
    public function sendBatch(array $requests): array
    {
        $payload = [];
        $requestIdToIndex = [];

        foreach ($requests as $index => $request) {
            $id = $this->requestId++;
            $requestIdToIndex[$id] = $index;
            $payload[] = [
                'jsonrpc' => '2.0',
                'method' => $request['method'],
                'params' => $request['params'] ?? [],
                'id' => $id,
            ];
        }

        try {
            $response = $this->client->post($this->url, [
                'json' => $payload,
            ]);

            $bodies = json_decode($response->getBody()->getContents(), true);

            // 初始化结果数组
            $results = array_fill(0, count($requests), null);

            foreach ($bodies as $body) {
                $id = $body['id'] ?? null;
                $originalIndex = $requestIdToIndex[$id] ?? null;

                if ($originalIndex === null) {
                    continue;
                }

                $originalRequest = $requests[$originalIndex];
                $context = $originalRequest['context'] ?? [];
                $method = $originalRequest['method'];

                if (isset($body['error'])) {
                    $error = $body['error'];
                    $message = $error['message'] ?? 'Unknown RPC error';
                    $code = $error['code'] ?? -1;
                    $data = $error['data'] ?? null;

                    $results[$originalIndex] = $this->parseRpcError($code, $message, $data, $method, $context);
                } else {
                    $results[$originalIndex] = $body['result'] ?? null;
                }
            }

            return $results;
        } catch (ConnectException $e) {
            throw new NetworkError(
                'RPC 批量连接失败: '.$e->getMessage(),
                'connect',
                ['url' => $this->url]
            );
        } catch (GuzzleException $e) {
            if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout')) {
                throw new TimeoutError(
                    'RPC 批量请求超时: '.$e->getMessage(),
                    'sendBatch',
                    'timeout',
                    ['url' => $this->url]
                );
            }

            throw new NetworkError(
                'RPC 批量请求失败: '.$e->getMessage(),
                'request',
                ['url' => $this->url]
            );
        }
    }

    /**
     * 解析 RPC 错误并返回对应的异常
     *
     * @param  int  $code  RPC 错误代码
     * @param  string  $message  RPC 错误信息
     * @param  mixed  $data  RPC 错误数据
     * @param  string  $method  RPC 方法名
     * @param  array  $context  上下文信息
     */
    private function parseRpcError(int $code, string $message, mixed $data, string $method, array $context): \Throwable
    {
        $lowerMessage = strtolower($message);
        $transaction = $context['transaction'] ?? null;

        // 判断操作类型
        $action = match ($method) {
            'eth_call' => 'call',
            'eth_estimateGas' => 'estimateGas',
            'eth_sendRawTransaction' => 'sendTransaction',
            'eth_getTransactionReceipt' => 'getTransactionResult',
            default => 'unknown',
        };

        // 检查余额不足
        if (
            str_contains($lowerMessage, 'insufficient funds') ||
            str_contains($lowerMessage, 'insufficient balance') ||
            str_contains($lowerMessage, 'not enough') ||
            str_contains($lowerMessage, 'gas required exceeds allowance')
        ) {
            return InsufficientFundsError::fromRpcError($code, $message, $transaction);
        }

        // 检查 nonce 问题
        if (
            str_contains($lowerMessage, 'nonce too low') ||
            str_contains($lowerMessage, 'nonce has already been used') ||
            str_contains($lowerMessage, 'replacement transaction underpriced')
        ) {
            return NonceExpiredError::fromRpcError($code, $message, $transaction);
        }

        // 检查执行错误 (revert, out of gas 等)
        if (
            str_contains($lowerMessage, 'execution reverted') ||
            str_contains($lowerMessage, 'revert') ||
            str_contains($lowerMessage, 'out of gas') ||
            str_contains($lowerMessage, 'gas limit reached') ||
            str_contains($lowerMessage, 'invalid opcode') ||
            str_contains($lowerMessage, 'stack underflow') ||
            str_contains($lowerMessage, 'always failing transaction') ||
            $code === 3 || // EIP-1474: execution error
            $code === -32000 // 通用执行错误
        ) {
            return CallExceptionError::fromRpcError(
                $code,
                $message,
                is_string($data) ? $data : null,
                $action,
                $transaction
            );
        }

        // 默认返回 ServerError
        return ServerError::fromRpcError($code, $message, $this->url);
    }

    /**
     * 获取链 ID
     */
    public function getChainId(): int
    {
        if ($this->chainId === null) {
            $result = $this->send('eth_chainId');
            $this->chainId = Hex::toInt($result);
        }

        return $this->chainId;
    }

    /**
     * 获取网络信息
     *
     * @return array{chainId: int, name: string}
     */
    public function getNetwork(): array
    {
        $chainId = $this->getChainId();

        $networkNames = [
            1 => 'mainnet',
            5 => 'goerli',
            11155111 => 'sepolia',
            137 => 'polygon',
            80001 => 'mumbai',
            56 => 'bsc',
            97 => 'bsc-testnet',
            42161 => 'arbitrum',
            421613 => 'arbitrum-goerli',
            10 => 'optimism',
            420 => 'optimism-goerli',
            43114 => 'avalanche',
            43113 => 'avalanche-fuji',
        ];

        return [
            'chainId' => $chainId,
            'name' => $networkNames[$chainId] ?? 'unknown',
        ];
    }

    /**
     * 获取当前区块号
     */
    public function getBlockNumber(): int
    {
        $result = $this->send('eth_blockNumber');

        return Hex::toInt($result);
    }

    /**
     * 获取账户余额
     *
     * @param  string  $address  账户地址
     * @param  string  $blockTag  区块标签, 默认 'latest'
     * @return string Wei 余额 (BigInt 字符串)
     */
    public function getBalance(string $address, string $blockTag = 'latest'): string
    {
        $result = $this->send('eth_getBalance', [$address, $blockTag]);

        return Hex::toBigInt($result);
    }

    /**
     * 获取账户交易计数 (nonce)
     *
     * @param  string  $address  账户地址
     * @param  string  $blockTag  区块标签, 默认 'latest'
     */
    public function getTransactionCount(string $address, string $blockTag = 'latest'): int
    {
        $result = $this->send('eth_getTransactionCount', [$address, $blockTag]);

        return Hex::toInt($result);
    }

    /**
     * 获取当前 gas 价格
     *
     * @return string Wei (BigInt 字符串)
     */
    public function getGasPrice(): string
    {
        $result = $this->send('eth_gasPrice');

        return Hex::toBigInt($result);
    }

    /**
     * 获取费用数据 (EIP-1559)
     *
     * @return array{gasPrice: string, maxFeePerGas: string|null, maxPriorityFeePerGas: string|null}
     */
    public function getFeeData(): array
    {
        $gasPrice = $this->getGasPrice();

        // 尝试获取 EIP-1559 费用
        try {
            $block = $this->getBlock('latest');
            $baseFeePerGas = $block['baseFeePerGas'] ?? null;

            if ($baseFeePerGas !== null) {
                $baseFee = Hex::toBigInt($baseFeePerGas);
                // maxPriorityFeePerGas 默认 1.5 Gwei
                $maxPriorityFeePerGas = '1500000000';
                // maxFeePerGas = baseFee * 2 + maxPriorityFeePerGas
                $maxFeePerGas = bcadd(bcmul($baseFee, '2'), $maxPriorityFeePerGas);

                return [
                    'gasPrice' => $gasPrice,
                    'maxFeePerGas' => $maxFeePerGas,
                    'maxPriorityFeePerGas' => $maxPriorityFeePerGas,
                ];
            }
        } catch (\Throwable) {
            // 忽略, 使用 legacy gas price
        }

        return [
            'gasPrice' => $gasPrice,
            'maxFeePerGas' => null,
            'maxPriorityFeePerGas' => null,
        ];
    }

    /**
     * 估算 gas
     *
     * @param  array  $transaction  交易对象
     * @return string gas 数量 (BigInt 字符串)
     *
     * @throws CallExceptionError
     * @throws InsufficientFundsError
     */
    public function estimateGas(array $transaction): string
    {
        $tx = $this->formatTransaction($transaction);
        $result = $this->send('eth_estimateGas', [$tx], ['transaction' => $tx]);

        return Hex::toBigInt($result);
    }

    /**
     * 调用合约方法 (只读, 不上链)
     *
     * @param  array  $transaction  交易对象
     * @param  string  $blockTag  区块标签
     * @return string 返回数据
     *
     * @throws CallExceptionError
     */
    public function call(array $transaction, string $blockTag = 'latest'): string
    {
        $tx = $this->formatTransaction($transaction);
        $result = $this->send('eth_call', [$tx, $blockTag], ['transaction' => $tx]);

        return $result;
    }

    /**
     * 发送已签名的原始交易
     *
     * @param  string  $signedTransaction  已签名的交易数据
     * @return string 交易哈希
     *
     * @throws CallExceptionError
     * @throws InsufficientFundsError
     * @throws NonceExpiredError
     */
    public function sendRawTransaction(string $signedTransaction): string
    {
        return $this->send('eth_sendRawTransaction', [$signedTransaction], [
            'signedTransaction' => $signedTransaction,
        ]);
    }

    /**
     * 获取交易信息
     *
     * @param  string  $hash  交易哈希
     */
    public function getTransaction(string $hash): ?array
    {
        return $this->send('eth_getTransactionByHash', [$hash]);
    }

    /**
     * 获取交易回执
     *
     * @param  string  $hash  交易哈希
     */
    public function getTransactionReceipt(string $hash): ?array
    {
        return $this->send('eth_getTransactionReceipt', [$hash]);
    }

    /**
     * 等待交易确认
     *
     * @param  string  $hash  交易哈希
     * @param  int  $confirmations  确认数
     * @param  int  $timeout  超时时间 (秒)
     * @return array 交易回执
     *
     * @throws TimeoutError
     */
    public function waitForTransaction(string $hash, int $confirmations = 1, int $timeout = 60): array
    {
        $startTime = time();

        while (true) {
            $receipt = $this->getTransactionReceipt($hash);

            if ($receipt !== null) {
                if ($confirmations <= 1) {
                    return $receipt;
                }

                $receiptBlock = Hex::toInt($receipt['blockNumber']);
                $currentBlock = $this->getBlockNumber();

                if ($currentBlock - $receiptBlock + 1 >= $confirmations) {
                    return $receipt;
                }
            }

            if (time() - $startTime > $timeout) {
                throw new TimeoutError(
                    "等待交易 {$hash} 超时",
                    'waitForTransaction',
                    'timeout',
                    ['hash' => $hash, 'timeout' => $timeout]
                );
            }

            usleep(1000000); // 1 秒
        }
    }

    /**
     * 获取区块信息
     *
     * @param  string|int  $blockHashOrNumber  区块哈希或区块号
     * @param  bool  $includeTransactions  是否包含完整交易
     */
    public function getBlock(string|int $blockHashOrNumber, bool $includeTransactions = false): ?array
    {
        if (is_int($blockHashOrNumber)) {
            $blockHashOrNumber = Hex::fromInt($blockHashOrNumber);
        }

        // 判断是哈希还是区块号/标签
        if (strlen($blockHashOrNumber) === 66 && str_starts_with($blockHashOrNumber, '0x')) {
            return $this->send('eth_getBlockByHash', [$blockHashOrNumber, $includeTransactions]);
        }

        return $this->send('eth_getBlockByNumber', [$blockHashOrNumber, $includeTransactions]);
    }

    /**
     * 获取合约代码
     *
     * @param  string  $address  合约地址
     * @param  string  $blockTag  区块标签
     */
    public function getCode(string $address, string $blockTag = 'latest'): string
    {
        return $this->send('eth_getCode', [$address, $blockTag]);
    }

    /**
     * 获取事件日志
     *
     * @param  array  $filter  过滤条件
     */
    public function getLogs(array $filter): array
    {
        $formattedFilter = [];

        if (isset($filter['address'])) {
            $formattedFilter['address'] = $filter['address'];
        }
        if (isset($filter['topics'])) {
            $formattedFilter['topics'] = $filter['topics'];
        }
        if (isset($filter['fromBlock'])) {
            $formattedFilter['fromBlock'] = is_int($filter['fromBlock'])
                ? Hex::fromInt($filter['fromBlock'])
                : $filter['fromBlock'];
        }
        if (isset($filter['toBlock'])) {
            $formattedFilter['toBlock'] = is_int($filter['toBlock'])
                ? Hex::fromInt($filter['toBlock'])
                : $filter['toBlock'];
        }

        return $this->send('eth_getLogs', [$formattedFilter]);
    }

    /**
     * 格式化交易对象
     */
    private function formatTransaction(array $transaction): array
    {
        $formatted = [];

        if (isset($transaction['from'])) {
            $formatted['from'] = $transaction['from'];
        }
        if (isset($transaction['to'])) {
            $formatted['to'] = $transaction['to'];
        }
        if (isset($transaction['data'])) {
            $formatted['data'] = $transaction['data'];
        }
        if (isset($transaction['value'])) {
            $formatted['value'] = is_string($transaction['value']) && ! str_starts_with($transaction['value'], '0x')
                ? Hex::fromBigInt($transaction['value'])
                : $transaction['value'];
        }
        if (isset($transaction['gas'])) {
            $formatted['gas'] = is_int($transaction['gas'])
                ? Hex::fromInt($transaction['gas'])
                : $transaction['gas'];
        }
        if (isset($transaction['gasPrice'])) {
            $formatted['gasPrice'] = is_string($transaction['gasPrice']) && ! str_starts_with($transaction['gasPrice'], '0x')
                ? Hex::fromBigInt($transaction['gasPrice'])
                : $transaction['gasPrice'];
        }
        if (isset($transaction['maxFeePerGas'])) {
            $formatted['maxFeePerGas'] = is_string($transaction['maxFeePerGas']) && ! str_starts_with($transaction['maxFeePerGas'], '0x')
                ? Hex::fromBigInt($transaction['maxFeePerGas'])
                : $transaction['maxFeePerGas'];
        }
        if (isset($transaction['maxPriorityFeePerGas'])) {
            $formatted['maxPriorityFeePerGas'] = is_string($transaction['maxPriorityFeePerGas']) && ! str_starts_with($transaction['maxPriorityFeePerGas'], '0x')
                ? Hex::fromBigInt($transaction['maxPriorityFeePerGas'])
                : $transaction['maxPriorityFeePerGas'];
        }
        if (isset($transaction['nonce'])) {
            $formatted['nonce'] = is_int($transaction['nonce'])
                ? Hex::fromInt($transaction['nonce'])
                : $transaction['nonce'];
        }

        return $formatted;
    }
}
