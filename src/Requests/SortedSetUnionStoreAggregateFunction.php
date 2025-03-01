<?php
declare(strict_types=1);

namespace Momento\Requests;

use Cache_client\_SortedSetUnionStoreRequest\AggregateFunction;

class SortedSetUnionStoreAggregateFunction {
    const SUM = AggregateFunction::SUM;
    const MIN = AggregateFunction::MIN;
    const MAX = AggregateFunction::MAX;
}
