<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: store.proto

namespace GPBMetadata;

class Store
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            '
�
store.protostore"v
_StoreValue
bytes_value (H 
string_value (	H 
integer_value (H 
double_value (H B
value"
_StoreGetRequest
key (	"6
_StoreGetResponse!
value (2.store._StoreValue"B
_StorePutRequest
key (	!
value (2.store._StoreValue"
_StorePutResponse""
_StoreDeleteRequest
key (	"
_StoreDeleteResponse2�
Store:
Get.store._StoreGetRequest.store._StoreGetResponse" :
Put.store._StorePutRequest.store._StorePutResponse" C
Delete.store._StoreDeleteRequest.store._StoreDeleteResponse" BW

grpc.storePZ0github.com/momentohq/client-sdk-go;client_sdk_go�Momento.Protos.Storebproto3'
        , true);

        static::$is_initialized = true;
    }
}

