# PHP Benchmarks

## loadgen.php

Ran with defaults defined in script: 100 byte payload with 100,000 get/set pairs. Raw nanosecond values for gets and 
sets were written to `gets.txt` and `sets.txt`. First request includes instantiating the client. No errors were
encountered.

## html directory

I set up an apache server with `StartServers` and `MaxRequestWorkers` both set to 1. After modifying the gRPC data
client to use the `force_new` flag (which we will presumably never want to expose) in one of the two php files, I ran
`curl` to request each page 1000 times. Results were recorded in `force_new.out` and `persistent.out`.

## future direction

I'd love to dockerize all of these benchmarks once they're presentable in the same way the integration tests are 
accessible via docker.
