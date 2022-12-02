#!/bin/sh

for i in {1..1000}; do echo `curl -s http://localhost/index-force-new.php`; done > force_new.out

