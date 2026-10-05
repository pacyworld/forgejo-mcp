<?php
/**
 * Forgejo MCP Server — tools/list size measurement and budget gate
 *
 * Serializes the tool catalog the way OpenAI-style function calling sends it
 * to a model and reports its cost. Two metrics:
 *
 *   bytes   UTF-8 length of the serialized function-array JSON. Tokenizer
 *           independent; the budget gate enforces this metric because it is
 *           deterministic on any CI runner with only PHP available.
 *   tokens  Qwen3 tokenizer count, when the optional tests/count-tokens.py
 *           helper can run (python3 with the 'tokenizers' module and network
 *           access). Published in PRs as the human-facing number.
 *
 * Usage:
 *   php tests/measure-tools-list.php                 Print metrics
 *   php tests/measure-tools-list.php --emit=FILE     Also write the JSON
 *   php tests/measure-tools-list.php --check         Fail when tests/tools-list.budget is exceeded
 *
 * Budget file format (tests/tools-list.budget), one JSON object:
 *   {"bytes": int, "tokens": int|null}
 * Lower than budget is fine; update the budget deliberately in the same PR
 * that changes the schema.
 *
 * @package    ForgejoMCP
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

require_once dirname(__DIR__) . '/system/bootstrap.inc.php';

use EnchiladaMCP\ToolRegistry;
use Forgejo\InstanceManager;

$manager = InstanceManager::fromFile(__DIR__ . '/fixtures/transport-instances.json');

$registry = new ToolRegistry();
$toolDir = APPLICATION_ROOT . 'tools';
foreach (scandir($toolDir) as $entry) {
	if (substr($entry, -4) !== '.php') continue;
	$className = basename($entry, '.php');
	require_once $toolDir . DIRECTORY_SEPARATOR . $entry;
	if (class_exists($className, false)) {
		$registry->register(new $className($manager));
	}
}

// OpenAI function-calling wire form: what most MCP->LLM bridges send.
$functions = array_map(fn($tool) => [
	'type' => 'function',
	'function' => [
		'name' => $tool['name'],
		'description' => $tool['description'],
		'parameters' => $tool['inputSchema'],
	] + array_diff_key($tool, array_flip(['name', 'description', 'inputSchema'])),
], $registry->listTools());

$json = json_encode($functions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$bytes = strlen($json);

// Optional Qwen3 token count via the python helper. Fed via a temp file:
// portable sh has no herestring and the payload exceeds ARG_MAX on some
// hosts once the catalog grows.
$tokens = null;
if (function_exists('shell_exec')) {
	$tmp = tempnam(sys_get_temp_dir(), 'tools-list-');
	file_put_contents($tmp, $json);
	$out = shell_exec('python3 ' . escapeshellarg(__DIR__ . '/count-tokens.py') . ' ' . escapeshellarg($tmp) . ' 2>/dev/null');
	unlink($tmp);
	if ($out !== null && ctype_digit(trim($out))) {
		$tokens = (int)trim($out);
	}
}

$emit = null;
$check = in_array('--check', $argv ?? [], true);
foreach ($argv ?? [] as $arg) {
	if (str_starts_with($arg, '--emit=')) {
		$emit = substr($arg, 7);
	}
}

if ($emit !== null) {
	file_put_contents($emit, $json);
}

printf("tools: %d\nbytes: %d\ntokens: %s\n", count($functions), $bytes, $tokens === null ? 'n/a' : (string)$tokens);

if ($check) {
	$budgetFile = __DIR__ . '/tools-list.budget';
	if (!is_file($budgetFile)) {
		fwrite(STDERR, "budget: missing {$budgetFile}\n");
		exit(1);
	}
	$budget = json_decode(file_get_contents($budgetFile), true);
	$over = $bytes > (int)$budget['bytes'] || ($tokens !== null && $budget['tokens'] !== null && $tokens > (int)$budget['tokens']);
	if ($over) {
		fwrite(STDERR, sprintf(
			"budget: EXCEEDED — bytes %d/%d, tokens %s/%s\n",
			$bytes, $budget['bytes'], $tokens === null ? 'n/a' : $tokens, $budget['tokens'] ?? 'n/a'
		));
		exit(1);
	}
	printf("budget: OK — bytes %d/%d, tokens %s/%s\n", $bytes, $budget['bytes'], $tokens === null ? 'n/a' : $tokens, $budget['tokens'] ?? 'n/a');
}
