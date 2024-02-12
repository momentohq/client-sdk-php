<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: permissionmessages.proto

namespace Permission_messages;

use UnexpectedValueException;

/**
 * Aliases for categories of functionality.
 *
 * Protobuf type <code>permission_messages.TopicRole</code>
 */
class TopicRole
{
    /**
     * Generated from protobuf enum <code>TopicPermitNone = 0;</code>
     */
    const TopicPermitNone = 0;
    /**
     * Restricts access to apis that read and write data from topics: No higher level resource description or modification.
     *
     * Generated from protobuf enum <code>TopicReadWrite = 1;</code>
     */
    const TopicReadWrite = 1;
    /**
     * Restricts access to apis that read from topics: No higher level resource description or modification.
     *
     * Generated from protobuf enum <code>TopicReadOnly = 2;</code>
     */
    const TopicReadOnly = 2;
    /**
     * Restricts access to apis that write from topics: No higher level resource description or modification.
     *
     * Generated from protobuf enum <code>TopicWriteOnly = 3;</code>
     */
    const TopicWriteOnly = 3;

    private static $valueToName = [
        self::TopicPermitNone => 'TopicPermitNone',
        self::TopicReadWrite => 'TopicReadWrite',
        self::TopicReadOnly => 'TopicReadOnly',
        self::TopicWriteOnly => 'TopicWriteOnly',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

