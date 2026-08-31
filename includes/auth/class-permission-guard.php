<?php
namespace RankOut_Connector\Auth;

if (!defined('ABSPATH')) {
    exit;
}

class Permission_Guard
{
    private $token_manager;

    public function __construct(Token_Manager $token_manager)
    {
        $this->token_manager = $token_manager;
    }

    public function can_use_tool($token_id, $tool_name)
    {
        $allowed = $this->get_allowed_tools($token_id);
        if (in_array('*', $allowed, true)) {
            return true;
        }
        return in_array($tool_name, $allowed, true);
    }

    public function can_use_tool_with_scope(array $allowed_tools, $tool_name)
    {
        if (in_array('*', $allowed_tools, true)) {
            return true;
        }

        if (in_array($tool_name, $allowed_tools, true)) {
            return true;
        }



        foreach ($allowed_tools as $pattern) {
            if (false !== strpos($pattern, '*') && fnmatch($pattern, $tool_name)) {
                return true;
            }
        }
        return false;
    }

    public function get_allowed_tools($token_id)
    {
        return $this->token_manager->get_allowed_tools($token_id);
    }
}
