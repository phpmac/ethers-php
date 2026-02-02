<?php

declare(strict_types=1);

namespace Ethers\Contract;

use Ethers\Utils\Keccak;
use InvalidArgumentException;

/**
 * Interface_
 * 合约接口, 用于解析 ABI 和编码/解码数据
 *
 * 参考 ethers.js v6 的 Interface
 * 注: PHP 不允许使用 Interface 作为类名
 *
 * 支持两种 ABI 格式:
 * 1. JSON ABI (标准格式)
 * 2. 人类可读 ABI (Human-Readable ABI)
 *
 * 人类可读 ABI 示例:
 * - "function name() view returns (string)"
 * - "function transfer(address to, uint256 amount) returns (bool)"
 * - "event Transfer(address indexed from, address indexed to, uint256 value)"
 * - "constructor(string name, string symbol)"
 */
class Interface_
{
    private array $abi;

    private array $functions = [];

    private array $events = [];

    private array $errors = [];

    private ?array $constructor = null;

    private AbiCoder $abiCoder;

    /**
     * 构造函数
     *
     * @param  array|string  $abi  ABI 定义 (JSON 字符串, JSON 数组, 或人类可读格式数组)
     */
    public function __construct(array|string $abi)
    {
        if (is_string($abi)) {
            // 尝试解析为 JSON
            $decoded = json_decode($abi, true);
            if ($decoded !== null) {
                $abi = $decoded;
            } else {
                // 单个人类可读 ABI 字符串
                $abi = [$abi];
            }
        }

        // 检查是否为人类可读格式 (数组中的元素是字符串)
        if (! empty($abi) && is_string(reset($abi))) {
            $abi = $this->parseHumanReadableAbi($abi);
        }

        $this->abi = $abi;
        $this->abiCoder = new AbiCoder;
        $this->parseAbi();
    }

    /**
     * 静态方法: 从人类可读 ABI 创建 Interface
     *
     * @param  array  $fragments  人类可读 ABI 片段数组
     */
    public static function from(array $fragments): self
    {
        return new self($fragments);
    }

    /**
     * 解析人类可读 ABI 为标准 JSON ABI 格式
     *
     * @param  array  $fragments  人类可读 ABI 片段数组
     * @return array 标准 JSON ABI
     */
    private function parseHumanReadableAbi(array $fragments): array
    {
        $abi = [];

        foreach ($fragments as $fragment) {
            $fragment = trim($fragment);
            if (empty($fragment)) {
                continue;
            }

            $parsed = $this->parseFragment($fragment);
            if ($parsed !== null) {
                $abi[] = $parsed;
            }
        }

        return $abi;
    }

    /**
     * 解析单个人类可读 ABI 片段
     */
    private function parseFragment(string $fragment): ?array
    {
        // 移除多余空格
        $fragment = preg_replace('/\s+/', ' ', $fragment);

        // 解析 function
        if (preg_match('/^function\s+(\w+)\s*\(([^)]*)\)\s*(.*)?$/i', $fragment, $matches)) {
            return $this->parseFunctionFragment($matches);
        }

        // 解析 event
        if (preg_match('/^event\s+(\w+)\s*\(([^)]*)\)\s*$/i', $fragment, $matches)) {
            return $this->parseEventFragment($matches);
        }

        // 解析 constructor
        if (preg_match('/^constructor\s*\(([^)]*)\)\s*(.*)?$/i', $fragment, $matches)) {
            return $this->parseConstructorFragment($matches);
        }

        // 解析 error
        if (preg_match('/^error\s+(\w+)\s*\(([^)]*)\)\s*$/i', $fragment, $matches)) {
            return $this->parseErrorFragment($matches);
        }

        // 解析 receive
        if (preg_match('/^receive\s*\(\s*\)\s*external\s+payable\s*$/i', $fragment)) {
            return [
                'type' => 'receive',
                'stateMutability' => 'payable',
            ];
        }

        // 解析 fallback
        if (preg_match('/^fallback\s*\(\s*\)\s*external\s*(payable)?\s*$/i', $fragment, $matches)) {
            return [
                'type' => 'fallback',
                'stateMutability' => isset($matches[1]) ? 'payable' : 'nonpayable',
            ];
        }

        return null;
    }

    /**
     * 解析函数片段
     */
    private function parseFunctionFragment(array $matches): array
    {
        $name = $matches[1];
        $inputsStr = $matches[2];
        $modifiers = $matches[3] ?? '';

        $inputs = $this->parseParams($inputsStr);
        $outputs = [];
        $stateMutability = 'nonpayable';

        // 解析修饰符
        if (preg_match('/\b(view|pure|payable)\b/i', $modifiers, $m)) {
            $stateMutability = strtolower($m[1]);
        }

        // 解析返回值
        if (preg_match('/returns\s*\(([^)]*)\)/i', $modifiers, $m)) {
            $outputs = $this->parseParams($m[1]);
        }

        return [
            'type' => 'function',
            'name' => $name,
            'inputs' => $inputs,
            'outputs' => $outputs,
            'stateMutability' => $stateMutability,
        ];
    }

    /**
     * 解析事件片段
     */
    private function parseEventFragment(array $matches): array
    {
        $name = $matches[1];
        $paramsStr = $matches[2];

        $inputs = $this->parseParams($paramsStr, true);

        return [
            'type' => 'event',
            'name' => $name,
            'inputs' => $inputs,
            'anonymous' => false,
        ];
    }

    /**
     * 解析构造函数片段
     */
    private function parseConstructorFragment(array $matches): array
    {
        $paramsStr = $matches[1];
        $modifiers = $matches[2] ?? '';

        $inputs = $this->parseParams($paramsStr);
        $stateMutability = 'nonpayable';

        if (preg_match('/\bpayable\b/i', $modifiers)) {
            $stateMutability = 'payable';
        }

        return [
            'type' => 'constructor',
            'inputs' => $inputs,
            'stateMutability' => $stateMutability,
        ];
    }

    /**
     * 解析错误片段
     */
    private function parseErrorFragment(array $matches): array
    {
        $name = $matches[1];
        $paramsStr = $matches[2];

        $inputs = $this->parseParams($paramsStr);

        return [
            'type' => 'error',
            'name' => $name,
            'inputs' => $inputs,
        ];
    }

    /**
     * 解析参数列表
     *
     * @param  string  $paramsStr  参数字符串
     * @param  bool  $allowIndexed  是否允许 indexed 修饰符
     */
    private function parseParams(string $paramsStr, bool $allowIndexed = false): array
    {
        $paramsStr = trim($paramsStr);
        if (empty($paramsStr)) {
            return [];
        }

        $params = [];
        $parts = $this->splitParams($paramsStr);

        foreach ($parts as $index => $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            $param = $this->parseParam($part, $allowIndexed, $index);
            if ($param !== null) {
                $params[] = $param;
            }
        }

        return $params;
    }

    /**
     * 分割参数 (处理嵌套括号)
     */
    private function splitParams(string $paramsStr): array
    {
        $parts = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($paramsStr); $i++) {
            $char = $paramsStr[$i];

            if ($char === '(' || $char === '[') {
                $depth++;
                $current .= $char;
            } elseif ($char === ')' || $char === ']') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (! empty($current)) {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * 解析单个参数
     */
    private function parseParam(string $paramStr, bool $allowIndexed, int $index): ?array
    {
        $paramStr = trim($paramStr);
        $indexed = false;
        $name = '';

        // 检查 indexed
        if ($allowIndexed && preg_match('/\bindexed\b/i', $paramStr)) {
            $indexed = true;
            $paramStr = preg_replace('/\s*indexed\s*/i', ' ', $paramStr);
        }

        // 分割类型和名称
        $parts = preg_split('/\s+/', trim($paramStr));

        if (count($parts) === 1) {
            // 只有类型, 没有名称
            $type = $parts[0];
            $name = 'arg'.$index;
        } elseif (count($parts) === 2) {
            // 类型和名称
            $type = $parts[0];
            $name = $parts[1];
        } elseif (count($parts) >= 3 && ($parts[1] === 'memory' || $parts[1] === 'calldata' || $parts[1] === 'storage')) {
            // 类型 + 存储位置 + 名称
            $type = $parts[0];
            $name = $parts[2] ?? 'arg'.$index;
        } else {
            // 其他情况
            $type = $parts[0];
            $name = end($parts);
        }

        $result = [
            'name' => $name,
            'type' => $type,
        ];

        if ($allowIndexed) {
            $result['indexed'] = $indexed;
        }

        return $result;
    }

    /**
     * 解析 ABI
     */
    private function parseAbi(): void
    {
        foreach ($this->abi as $item) {
            $type = $item['type'] ?? '';

            if ($type === 'function') {
                $name = $item['name'];
                $signature = $this->buildSignature($name, $item['inputs'] ?? []);
                $selector = Keccak::functionSelector($signature);

                $this->functions[$name] = [
                    'name' => $name,
                    'signature' => $signature,
                    'selector' => $selector,
                    'inputs' => $item['inputs'] ?? [],
                    'outputs' => $item['outputs'] ?? [],
                    'stateMutability' => $item['stateMutability'] ?? 'nonpayable',
                ];

                // 同时用 selector 作为 key, 方便解码
                $this->functions[$selector] = $this->functions[$name];
            } elseif ($type === 'event') {
                $name = $item['name'];
                $signature = $this->buildSignature($name, $item['inputs'] ?? []);
                $topic = Keccak::eventTopic($signature);

                $this->events[$name] = [
                    'name' => $name,
                    'signature' => $signature,
                    'topic' => $topic,
                    'inputs' => $item['inputs'] ?? [],
                    'anonymous' => $item['anonymous'] ?? false,
                ];

                $this->events[$topic] = $this->events[$name];
            } elseif ($type === 'constructor') {
                $this->constructor = [
                    'type' => 'constructor',
                    'inputs' => $item['inputs'] ?? [],
                    'stateMutability' => $item['stateMutability'] ?? 'nonpayable',
                ];
            } elseif ($type === 'error') {
                $name = $item['name'];
                $signature = $this->buildSignature($name, $item['inputs'] ?? []);
                $selector = Keccak::functionSelector($signature);

                $this->errors[$name] = [
                    'name' => $name,
                    'signature' => $signature,
                    'selector' => $selector,
                    'inputs' => $item['inputs'] ?? [],
                ];

                $this->errors[$selector] = $this->errors[$name];
            }
        }
    }

    /**
     * 获取原始 ABI
     */
    public function getAbi(): array
    {
        return $this->abi;
    }

    /**
     * 获取构造函数定义
     */
    public function getConstructor(): ?array
    {
        return $this->constructor;
    }

    /**
     * 获取错误定义
     *
     * @param  string  $nameOrSelector  错误名或选择器
     */
    public function getError(string $nameOrSelector): ?array
    {
        return $this->errors[$nameOrSelector] ?? null;
    }

    /**
     * 编码构造函数参数 (用于部署)
     *
     * @param  array  $args  构造函数参数
     * @return string 编码后的数据 (不含 bytecode)
     */
    public function encodeDeploy(array $args = []): string
    {
        if ($this->constructor === null || empty($this->constructor['inputs'])) {
            if (! empty($args)) {
                throw new InvalidArgumentException('构造函数没有参数');
            }

            return '0x';
        }

        $types = array_map(fn ($input) => $input['type'], $this->constructor['inputs']);

        return $this->abiCoder->encode($types, $args);
    }

    /**
     * 解码错误数据
     *
     * @param  string  $data  错误数据
     * @return array{name: string, args: array}
     */
    public function decodeErrorResult(string $data): array
    {
        $selector = substr($data, 0, 10);
        $error = $this->getError($selector);

        if ($error === null) {
            throw new InvalidArgumentException('未知的错误选择器: '.$selector);
        }

        $encodedArgs = '0x'.substr($data, 10);
        $types = array_map(fn ($input) => $input['type'], $error['inputs']);
        $args = $this->abiCoder->decode($types, $encodedArgs);

        return [
            'name' => $error['name'],
            'args' => $args,
        ];
    }

    /**
     * 格式化 ABI 为指定格式
     *
     * @param  string  $format  格式: 'json', 'minimal', 'full'
     */
    public function format(string $format = 'minimal'): array|string
    {
        if ($format === 'json') {
            return json_encode($this->abi, JSON_PRETTY_PRINT);
        }

        $result = [];

        // 构造函数
        if ($this->constructor !== null) {
            $params = $this->formatParams($this->constructor['inputs']);
            $modifier = $this->constructor['stateMutability'] === 'payable' ? ' payable' : '';
            $result[] = "constructor({$params}){$modifier}";
        }

        // 函数
        foreach ($this->getAllFunctions() as $func) {
            $params = $this->formatParams($func['inputs']);
            $returns = '';
            if (! empty($func['outputs'])) {
                $returns = ' returns ('.$this->formatParams($func['outputs']).')';
            }
            $modifier = '';
            if ($func['stateMutability'] !== 'nonpayable') {
                $modifier = ' '.$func['stateMutability'];
            }
            $result[] = "function {$func['name']}({$params}){$modifier}{$returns}";
        }

        // 事件
        foreach ($this->getAllEvents() as $event) {
            $params = $this->formatEventParams($event['inputs']);
            $result[] = "event {$event['name']}({$params})";
        }

        // 错误
        foreach ($this->getAllErrors() as $error) {
            $params = $this->formatParams($error['inputs']);
            $result[] = "error {$error['name']}({$params})";
        }

        return $result;
    }

    /**
     * 格式化参数列表
     */
    private function formatParams(array $inputs): string
    {
        return implode(', ', array_map(function ($input) {
            $name = $input['name'] ?? '';

            return $input['type'].($name ? ' '.$name : '');
        }, $inputs));
    }

    /**
     * 格式化事件参数列表 (包含 indexed)
     */
    private function formatEventParams(array $inputs): string
    {
        return implode(', ', array_map(function ($input) {
            $indexed = ($input['indexed'] ?? false) ? 'indexed ' : '';
            $name = $input['name'] ?? '';

            return $input['type'].' '.$indexed.$name;
        }, $inputs));
    }

    /**
     * 获取所有错误
     */
    public function getAllErrors(): array
    {
        return array_filter($this->errors, fn ($key) => ! str_starts_with($key, '0x'), ARRAY_FILTER_USE_KEY);
    }

    /**
     * 构建函数/事件签名
     */
    private function buildSignature(string $name, array $inputs): string
    {
        $types = array_map(fn ($input) => $this->getCanonicalType($input), $inputs);

        return $name.'('.implode(',', $types).')';
    }

    /**
     * 获取规范类型
     */
    private function getCanonicalType(array $input): string
    {
        $type = $input['type'];

        // 处理元组类型
        if ($type === 'tuple' || str_starts_with($type, 'tuple')) {
            $components = $input['components'] ?? [];
            $componentTypes = array_map(fn ($c) => $this->getCanonicalType($c), $components);
            $tupleType = '('.implode(',', $componentTypes).')';

            // 处理元组数组
            if (str_ends_with($type, '[]')) {
                return $tupleType.'[]';
            }
            if (preg_match('/\[(\d+)\]$/', $type, $matches)) {
                return $tupleType.'['.$matches[1].']';
            }

            return $tupleType;
        }

        return $type;
    }

    /**
     * 获取函数定义
     *
     * @param  string  $nameOrSelector  函数名或选择器
     */
    public function getFunction(string $nameOrSelector): ?array
    {
        return $this->functions[$nameOrSelector] ?? null;
    }

    /**
     * 获取事件定义
     *
     * @param  string  $nameOrTopic  事件名或主题
     */
    public function getEvent(string $nameOrTopic): ?array
    {
        return $this->events[$nameOrTopic] ?? null;
    }

    /**
     * 编码函数数据
     *
     * @param  string  $name  函数名
     * @param  array  $args  参数
     * @return string 编码后的数据
     */
    public function encodeFunctionData(string $name, array $args = []): string
    {
        $func = $this->getFunction($name);
        if ($func === null) {
            throw new InvalidArgumentException("函数 {$name} 不存在");
        }

        $types = array_map(fn ($input) => $input['type'], $func['inputs']);
        $encoded = $this->abiCoder->encode($types, $args);

        return $func['selector'].substr($encoded, 2);
    }

    /**
     * 解码函数数据
     *
     * @param  string  $name  函数名
     * @param  string  $data  编码的数据
     * @return array 解码后的值
     */
    public function decodeFunctionData(string $name, string $data): array
    {
        $func = $this->getFunction($name);
        if ($func === null) {
            throw new InvalidArgumentException("函数 {$name} 不存在");
        }

        // 移除函数选择器
        $data = '0x'.substr($data, 10);
        $types = array_map(fn ($input) => $input['type'], $func['inputs']);

        return $this->abiCoder->decode($types, $data);
    }

    /**
     * 解码函数返回值
     *
     * @param  string  $name  函数名
     * @param  string  $data  返回数据
     * @return array 解码后的值
     */
    public function decodeFunctionResult(string $name, string $data): array
    {
        $func = $this->getFunction($name);
        if ($func === null) {
            throw new InvalidArgumentException("函数 {$name} 不存在");
        }

        $types = array_map(fn ($output) => $output['type'], $func['outputs']);

        return $this->abiCoder->decode($types, $data);
    }

    /**
     * 编码事件主题
     *
     * @param  string  $name  事件名
     * @param  array  $args  索引参数
     * @return array 主题数组
     */
    public function encodeEventTopics(string $name, array $args = []): array
    {
        $event = $this->getEvent($name);
        if ($event === null) {
            throw new InvalidArgumentException("事件 {$name} 不存在");
        }

        $topics = [$event['topic']];

        foreach ($event['inputs'] as $i => $input) {
            if (! ($input['indexed'] ?? false)) {
                continue;
            }

            if (isset($args[$i]) && $args[$i] !== null) {
                if ($input['type'] === 'address') {
                    $topics[] = '0x'.str_pad(substr($args[$i], 2), 64, '0', STR_PAD_LEFT);
                } elseif (str_starts_with($input['type'], 'uint') || str_starts_with($input['type'], 'int')) {
                    $topics[] = '0x'.str_pad(dechex((int) $args[$i]), 64, '0', STR_PAD_LEFT);
                } else {
                    $topics[] = null;
                }
            } else {
                $topics[] = null;
            }
        }

        return $topics;
    }

    /**
     * 解码事件日志
     *
     * @param  array  $log  日志对象
     * @return array 解码后的事件数据
     */
    public function decodeEventLog(array $log): array
    {
        $topic0 = $log['topics'][0] ?? null;
        if ($topic0 === null) {
            throw new InvalidArgumentException('日志缺少主题');
        }

        $event = $this->getEvent($topic0);
        if ($event === null) {
            throw new InvalidArgumentException('未知的事件主题');
        }

        $result = [
            'name' => $event['name'],
            'args' => [],
        ];

        $topicIndex = 1;
        $nonIndexedTypes = [];
        $nonIndexedNames = [];

        foreach ($event['inputs'] as $input) {
            if ($input['indexed'] ?? false) {
                // 索引参数从 topics 中解码
                $topicData = $log['topics'][$topicIndex] ?? null;
                if ($topicData !== null) {
                    if ($input['type'] === 'address') {
                        $result['args'][$input['name']] = '0x'.substr($topicData, 26);
                    } else {
                        [$value] = $this->abiCoder->decode([$input['type']], $topicData);
                        $result['args'][$input['name']] = $value;
                    }
                }
                $topicIndex++;
            } else {
                // 非索引参数从 data 中解码
                $nonIndexedTypes[] = $input['type'];
                $nonIndexedNames[] = $input['name'];
            }
        }

        // 解码非索引参数
        if (! empty($nonIndexedTypes) && isset($log['data']) && $log['data'] !== '0x') {
            $decoded = $this->abiCoder->decode($nonIndexedTypes, $log['data']);
            foreach ($nonIndexedNames as $i => $name) {
                $result['args'][$name] = $decoded[$i];
            }
        }

        return $result;
    }

    /**
     * 获取所有函数
     */
    public function getAllFunctions(): array
    {
        return array_filter($this->functions, fn ($key) => ! str_starts_with($key, '0x'), ARRAY_FILTER_USE_KEY);
    }

    /**
     * 获取所有事件
     */
    public function getAllEvents(): array
    {
        return array_filter($this->events, fn ($key) => ! str_starts_with($key, '0x'), ARRAY_FILTER_USE_KEY);
    }
}
