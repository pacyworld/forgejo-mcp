<?php
/**
 * Forgejo MCP Server — Timeout Exception
 *
 * Thrown when an API request times out (curl CURLE_OPERATION_TIMEDOUT).
 *
 * Extends ClientException so existing catch (ClientException) handling
 * keeps working, and implements EnchiladaMCP\ToolWarningInterface so the
 * MCP layer returns the message as a NORMAL (non-error) tool result:
 * a timeout means the outcome is uncertain (the server may still have
 * completed the operation), not that the tool call failed.
 *
 * @package    ForgejoMCP\Forgejo
 * @author     Daniel Morante
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace Forgejo;

class TimeoutException extends ClientException implements \EnchiladaMCP\ToolWarningInterface
{
}
