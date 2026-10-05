<?php

namespace Forgejo;

/**
 * Forgejo MCP Server — Consolidated Tool Dispatcher
 *
 * Shared plumbing for action-dispatched tools: one public tool per resource
 * family, selected by an `action` enum, routing to the family's internal
 * methods. Per-action required parameters are enforced here because a flat
 * JSON Schema `required` list cannot express them (#10/#11).
 *
 * @package    ForgejoMCP
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */
abstract class ConsolidatedToolBase
{
	protected InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Dispatch an action to its handler after validating required params.
	 *
	 * @param string                      $tool Tool name (for error messages)
	 * @param string                      $action Requested action
	 * @param array<string,mixed>         $args Call arguments; null means "not provided"
	 * @param array<string,array{handler:array{0:object,1:string},required:string[],args:string[]}> $ops
	 *   Action table: handler is [instance, method], required must be present
	 *   and non-null, args lists every parameter the handler accepts (unknown
	 *   parameters are discarded before invocation).
	 * @return mixed
	 * @throws \InvalidArgumentException Unknown action or missing required parameter
	 */
	protected function dispatch(string $tool, string $action, array $args, array $ops): mixed
	{
		if (!isset($ops[$action])) {
			throw new \InvalidArgumentException("{$tool}: unknown action '{$action}'. Valid actions: " . implode(', ', array_keys($ops)));
		}

		unset($args['action']);
		$args = array_filter($args, fn($v) => $v !== null);

		$missing = array_diff($ops[$action]['required'], array_keys($args));
		if (!empty($missing)) {
			throw new \InvalidArgumentException("{$tool} action '{$action}': missing required parameter(s): " . implode(', ', $missing));
		}

		$args = array_intersect_key($args, array_flip($ops[$action]['args']));
		[$handler, $method] = $ops[$action]['handler'];
		return (new \ReflectionMethod($handler, $method))->invokeArgs($handler, $args);
	}
}
