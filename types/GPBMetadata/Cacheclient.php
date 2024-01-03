<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cacheclient.proto

namespace GPBMetadata;

class Cacheclient
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            '
�|
cacheclient.protocache_client" 
_GetRequest
	cache_key ("_
_GetResponse*
result (2.cache_client.ECacheResult

cache_body (
message (	"#
_DeleteRequest
	cache_key ("
_DeleteResponse"N
_SetRequest
	cache_key (

cache_body (
ttl_milliseconds ("K
_SetResponse*
result (2.cache_client.ECacheResult
message (	"Y
_SetIfNotExistsRequest
	cache_key (

cache_body (
ttl_milliseconds ("�
_SetIfNotExistsResponse?
stored (2-.cache_client._SetIfNotExistsResponse._StoredH F

not_stored (20.cache_client._SetIfNotExistsResponse._NotStoredH 	
_Stored

_NotStoredB
result"\'
_KeysExistRequest

cache_keys ("$
_KeysExistResponse
exists ("P
_IncrementRequest
	cache_key (
amount (
ttl_milliseconds ("#
_IncrementResponse
value ("�
_UpdateTtlRequest
	cache_key ("
increase_to_milliseconds (H "
decrease_to_milliseconds (H #
overwrite_to_milliseconds (H B

update_ttl"�
_UpdateTtlResponse4
set (2%.cache_client._UpdateTtlResponse._SetH ;
not_set (2(.cache_client._UpdateTtlResponse._NotSetH <
missing (2).cache_client._UpdateTtlResponse._MissingH 
_Set	
_NotSet

_MissingB
result"\'
_ItemGetTtlRequest
	cache_key ("�
_ItemGetTtlResponse9
found (2(.cache_client._ItemGetTtlResponse._FoundH =
missing (2*.cache_client._ItemGetTtlResponse._MissingH &
_Found
remaining_ttl_millis (

_MissingB
result"(
_ItemGetTypeRequest
	cache_key ("�
_ItemGetTypeResponse:
found (2).cache_client._ItemGetTypeResponse._FoundH >
missing (2+.cache_client._ItemGetTypeResponse._MissingH H
_Found>
	item_type (2+.cache_client._ItemGetTypeResponse.ItemType

_Missing"I
ItemType

SCALAR 

DICTIONARY
SET
LIST

SORTED_SETB
result"@
_DictionaryGetRequest
dictionary_name (
fields ("�
_DictionaryGetResponse<
found (2+.cache_client._DictionaryGetResponse._FoundH @
missing (2-.cache_client._DictionaryGetResponse._MissingH \\
_DictionaryGetResponsePart*
result (2.cache_client.ECacheResult

cache_body (X
_FoundN
items (2?.cache_client._DictionaryGetResponse._DictionaryGetResponsePart

_MissingB

dictionary"2
_DictionaryFetchRequest
dictionary_name ("9
_DictionaryFieldValuePair
field (
value ("�
_DictionaryFetchResponse>
found (2-.cache_client._DictionaryFetchResponse._FoundH B
missing (2/.cache_client._DictionaryFetchResponse._MissingH @
_Found6
items (2\'.cache_client._DictionaryFieldValuePair

_MissingB

dictionary"�
_DictionarySetRequest
dictionary_name (6
items (2\'.cache_client._DictionaryFieldValuePair
ttl_milliseconds (
refresh_ttl ("
_DictionarySetResponse"�
_DictionaryIncrementRequest
dictionary_name (
field (
amount (
ttl_milliseconds (
refresh_ttl ("-
_DictionaryIncrementResponse
value ("�
_DictionaryDeleteRequest
dictionary_name (;
some (2+.cache_client._DictionaryDeleteRequest.SomeH 9
all (2*.cache_client._DictionaryDeleteRequest.AllH 
Some
fields (
AllB
delete"
_DictionaryDeleteResponse"3
_DictionaryLengthRequest
dictionary_name ("�
_DictionaryLengthResponse?
found (2..cache_client._DictionaryLengthResponse._FoundH C
missing (20.cache_client._DictionaryLengthResponse._MissingH 
_Found
length (

_MissingB

dictionary"$
_SetFetchRequest
set_name ("�
_SetFetchResponse7
found (2&.cache_client._SetFetchResponse._FoundH ;
missing (2(.cache_client._SetFetchResponse._MissingH 
_Found
elements (

_MissingB
set"e
_SetUnionRequest
set_name (
elements (
ttl_milliseconds (
refresh_ttl ("
_SetUnionResponse"�
_SetDifferenceRequest
set_name (?
minuend (2,.cache_client._SetDifferenceRequest._MinuendH E

subtrahend (2/.cache_client._SetDifferenceRequest._SubtrahendH 
_Minuend
elements (�
_SubtrahendC
set (24.cache_client._SetDifferenceRequest._Subtrahend._SetH M
identity (29.cache_client._SetDifferenceRequest._Subtrahend._IdentityH 
_Set
elements (
	_IdentityB
subtrahend_setB

difference"�
_SetDifferenceResponse<
found (2+.cache_client._SetDifferenceResponse._FoundH @
missing (2-.cache_client._SetDifferenceResponse._MissingH 
_Found

_MissingB
set"9
_SetContainsRequest
set_name (
elements ("�
_SetContainsResponse:
found (2).cache_client._SetContainsResponse._FoundH >
missing (2+.cache_client._SetContainsResponse._MissingH 
_Found
contains (

_MissingB
set"%
_SetLengthRequest
set_name ("�
_SetLengthResponse8
found (2\'.cache_client._SetLengthResponse._FoundH <
missing (2).cache_client._SetLengthResponse._MissingH 
_Found
length (

_MissingB
set"1
_SetPopRequest
set_name (
count ("�
_SetPopResponse5
found (2$.cache_client._SetPopResponse._FoundH 9
missing (2&.cache_client._SetPopResponse._MissingH 
_Found
elements (

_MissingB
set"�
_ListConcatenateFrontRequest
	list_name (
values (
ttl_milliseconds (
refresh_ttl (
truncate_back_to_size ("4
_ListConcatenateFrontResponse
list_length ("�
_ListConcatenateBackRequest
	list_name (
values (
ttl_milliseconds (
refresh_ttl (
truncate_front_to_size ("3
_ListConcatenateBackResponse
list_length ("�
_ListPushFrontRequest
	list_name (
value (
ttl_milliseconds (
refresh_ttl (
truncate_back_to_size ("-
_ListPushFrontResponse
list_length ("�
_ListPushBackRequest
	list_name (
value (
ttl_milliseconds (
refresh_ttl (
truncate_front_to_size (",
_ListPushBackResponse
list_length (")
_ListPopFrontRequest
	list_name ("�
_ListPopFrontResponse;
found (2*.cache_client._ListPopFrontResponse._FoundH ?
missing (2,.cache_client._ListPopFrontResponse._MissingH ,
_Found
front (
list_length (

_MissingB
list"(
_ListPopBackRequest
	list_name ("�
_ListPopBackResponse:
found (2).cache_client._ListPopBackResponse._FoundH >
missing (2+.cache_client._ListPopBackResponse._MissingH +
_Found
back (
list_length (

_MissingB
list"0

_ListRange
begin_index (
count ("�
_ListEraseRequest
	list_name (;
some (2+.cache_client._ListEraseRequest._ListRangesH 3
all (2$.cache_client._ListEraseRequest._AllH 
_All7
_ListRanges(
ranges (2.cache_client._ListRangeB
erase"�
_ListEraseResponse8
found (2\'.cache_client._ListEraseResponse._FoundH <
missing (2).cache_client._ListEraseResponse._MissingH 
_Found
list_length (

_MissingB
list"T
_ListRemoveRequest
	list_name (!
all_elements_with_value (H B
remove"�
_ListRemoveResponse9
found (2(.cache_client._ListRemoveResponse._FoundH =
missing (2*.cache_client._ListRemoveResponse._MissingH 
_Found
list_length (

_MissingB
list"

_Unbounded"�
_ListFetchRequest
	list_name (3
unbounded_start (2.cache_client._UnboundedH 
inclusive_start (H 1
unbounded_end (2.cache_client._UnboundedH
exclusive_end (HB
start_indexB
	end_index"�
_ListRetainRequest
	list_name (3
unbounded_start (2.cache_client._UnboundedH 
inclusive_start (H 1
unbounded_end (2.cache_client._UnboundedH
exclusive_end (H
ttl_milliseconds (
refresh_ttl (B
start_indexB
	end_index"�
_ListRetainResponse9
found (2(.cache_client._ListRetainResponse._FoundH =
missing (2*.cache_client._ListRetainResponse._MissingH 
_Found
list_length (

_MissingB
list"�
_ListFetchResponse8
found (2\'.cache_client._ListFetchResponse._FoundH <
missing (2).cache_client._ListFetchResponse._MissingH 
_Found
values (

_MissingB
list"\'
_ListLengthRequest
	list_name ("�
_ListLengthResponse9
found (2(.cache_client._ListLengthResponse._FoundH =
missing (2*.cache_client._ListLengthResponse._MissingH 
_Found
length (

_MissingB
list"1
_SortedSetElement
value (
score ("�
_SortedSetPutRequest
set_name (1
elements (2.cache_client._SortedSetElement
ttl_milliseconds (
refresh_ttl ("
_SortedSetPutResponse"�
_SortedSetFetchRequest
set_name (9
order (2*.cache_client._SortedSetFetchRequest.Order
with_scores (A
by_index (2-.cache_client._SortedSetFetchRequest._ByIndexH A
by_score (2-.cache_client._SortedSetFetchRequest._ByScoreH �
_ByIndex3
unbounded_start (2.cache_client._UnboundedH 
inclusive_start_index (H 1
unbounded_end (2.cache_client._UnboundedH
exclusive_end_index (HB
startB
end�
_ByScore1
unbounded_min (2.cache_client._UnboundedH I
	min_score (24.cache_client._SortedSetFetchRequest._ByScore._ScoreH 1
unbounded_max (2.cache_client._UnboundedHI
	max_score (24.cache_client._SortedSetFetchRequest._ByScore._ScoreH
offset (
count (*
_Score
score (
	exclusive (B
minB
max"&
Order
	ASCENDING 

DESCENDINGB
range"�
_SortedSetFetchResponse=
found (2,.cache_client._SortedSetFetchResponse._FoundH A
missing (2..cache_client._SortedSetFetchResponse._MissingH �
_Found\\
values_with_scores (2>.cache_client._SortedSetFetchResponse._Found._ValuesWithScoresH F
values (24.cache_client._SortedSetFetchResponse._Found._ValuesH F
_ValuesWithScores1
elements (2.cache_client._SortedSetElement
_Values
values (B

elements

_MissingB

sorted_set"=
_SortedSetGetScoreRequest
set_name (
values ("�
_SortedSetGetScoreResponseI
found (28.cache_client._SortedSetGetScoreResponse._SortedSetFoundH M
missing (2:.cache_client._SortedSetGetScoreResponse._SortedSetMissingH [
_SortedSetGetScoreResponsePart*
result (2.cache_client.ECacheResult
score (l
_SortedSetFoundY
elements (2G.cache_client._SortedSetGetScoreResponse._SortedSetGetScoreResponsePart
_SortedSetMissingB

sorted_set"�
_SortedSetRemoveRequest
set_name (9
all (2*.cache_client._SortedSetRemoveRequest._AllH ;
some (2+.cache_client._SortedSetRemoveRequest._SomeH 
_All
_Some
values (B
remove_elements"
_SortedSetRemoveResponse"|
_SortedSetIncrementRequest
set_name (
value (
amount (
ttl_milliseconds (
refresh_ttl (",
_SortedSetIncrementResponse
score ("�
_SortedSetGetRankRequest
set_name (
value (;
order (2,.cache_client._SortedSetGetRankRequest.Order"&
Order
	ASCENDING 

DESCENDING"�
_SortedSetGetRankResponseQ
element_rank (29.cache_client._SortedSetGetRankResponse._RankResponsePartH L
missing (29.cache_client._SortedSetGetRankResponse._SortedSetMissingH M
_RankResponsePart*
result (2.cache_client.ECacheResult
rank (
_SortedSetMissingB
rank"+
_SortedSetLengthRequest
set_name ("�
_SortedSetLengthResponse>
found (2-.cache_client._SortedSetLengthResponse._FoundH B
missing (2/.cache_client._SortedSetLengthResponse._MissingH 
_Found
length (

_MissingB

sorted_set"�
_SortedSetLengthByScoreRequest
set_name (
inclusive_min (H 
exclusive_min (H 1
unbounded_min (2.cache_client._UnboundedH 
inclusive_max (H
exclusive_max (H1
unbounded_max (2.cache_client._UnboundedHB
minB
max"�
_SortedSetLengthByScoreResponseE
found (24.cache_client._SortedSetLengthByScoreResponse._FoundH I
missing (26.cache_client._SortedSetLengthByScoreResponse._MissingH 
_Found
length (

_MissingB

sorted_set*<
ECacheResult
Invalid 
Ok
Hit
Miss"2�
Scs>
Get.cache_client._GetRequest.cache_client._GetResponse" >
Set.cache_client._SetRequest.cache_client._SetResponse" _
SetIfNotExists$.cache_client._SetIfNotExistsRequest%.cache_client._SetIfNotExistsResponse" G
Delete.cache_client._DeleteRequest.cache_client._DeleteResponse" P
	KeysExist.cache_client._KeysExistRequest .cache_client._KeysExistResponse" P
	Increment.cache_client._IncrementRequest .cache_client._IncrementResponse" P
	UpdateTtl.cache_client._UpdateTtlRequest .cache_client._UpdateTtlResponse" S

ItemGetTtl .cache_client._ItemGetTtlRequest!.cache_client._ItemGetTtlResponse" V
ItemGetType!.cache_client._ItemGetTypeRequest".cache_client._ItemGetTypeResponse" \\
DictionaryGet#.cache_client._DictionaryGetRequest$.cache_client._DictionaryGetResponse" b
DictionaryFetch%.cache_client._DictionaryFetchRequest&.cache_client._DictionaryFetchResponse" \\
DictionarySet#.cache_client._DictionarySetRequest$.cache_client._DictionarySetResponse" n
DictionaryIncrement).cache_client._DictionaryIncrementRequest*.cache_client._DictionaryIncrementResponse" e
DictionaryDelete&.cache_client._DictionaryDeleteRequest\'.cache_client._DictionaryDeleteResponse" e
DictionaryLength&.cache_client._DictionaryLengthRequest\'.cache_client._DictionaryLengthResponse" M
SetFetch.cache_client._SetFetchRequest.cache_client._SetFetchResponse" M
SetUnion.cache_client._SetUnionRequest.cache_client._SetUnionResponse" \\
SetDifference#.cache_client._SetDifferenceRequest$.cache_client._SetDifferenceResponse" V
SetContains!.cache_client._SetContainsRequest".cache_client._SetContainsResponse" P
	SetLength.cache_client._SetLengthRequest .cache_client._SetLengthResponse" G
SetPop.cache_client._SetPopRequest.cache_client._SetPopResponse" \\
ListPushFront#.cache_client._ListPushFrontRequest$.cache_client._ListPushFrontResponse" Y
ListPushBack".cache_client._ListPushBackRequest#.cache_client._ListPushBackResponse" Y
ListPopFront".cache_client._ListPopFrontRequest#.cache_client._ListPopFrontResponse" V
ListPopBack!.cache_client._ListPopBackRequest".cache_client._ListPopBackResponse" P
	ListErase.cache_client._ListEraseRequest .cache_client._ListEraseResponse" S

ListRemove .cache_client._ListRemoveRequest!.cache_client._ListRemoveResponse" P
	ListFetch.cache_client._ListFetchRequest .cache_client._ListFetchResponse" S

ListLength .cache_client._ListLengthRequest!.cache_client._ListLengthResponse" q
ListConcatenateFront*.cache_client._ListConcatenateFrontRequest+.cache_client._ListConcatenateFrontResponse" n
ListConcatenateBack).cache_client._ListConcatenateBackRequest*.cache_client._ListConcatenateBackResponse" S

ListRetain .cache_client._ListRetainRequest!.cache_client._ListRetainResponse" Y
SortedSetPut".cache_client._SortedSetPutRequest#.cache_client._SortedSetPutResponse" _
SortedSetFetch$.cache_client._SortedSetFetchRequest%.cache_client._SortedSetFetchResponse" h
SortedSetGetScore\'.cache_client._SortedSetGetScoreRequest(.cache_client._SortedSetGetScoreResponse" b
SortedSetRemove%.cache_client._SortedSetRemoveRequest&.cache_client._SortedSetRemoveResponse" k
SortedSetIncrement(.cache_client._SortedSetIncrementRequest).cache_client._SortedSetIncrementResponse" e
SortedSetGetRank&.cache_client._SortedSetGetRankRequest\'.cache_client._SortedSetGetRankResponse" b
SortedSetLength%.cache_client._SortedSetLengthRequest&.cache_client._SortedSetLengthResponse" w
SortedSetLengthByScore,.cache_client._SortedSetLengthByScoreRequest-.cache_client._SortedSetLengthByScoreResponse" Bd
grpc.cache_clientPZ0github.com/momentohq/client-sdk-go;client_sdk_go�Momento.Protos.CacheClientbproto3'
        , true);

        static::$is_initialized = true;
    }
}

