<img src="https://docs.momentohq.com/img/logo.svg" alt="logo" width="400"/>

# Using Momento With A Proxy

Momento `CacheClient` connections can be proxied with relative ease. We'll provide an example here, using
[HA Proxy](https://www.haproxy.com/) as a layer 4 proxy on localhost.

## Configuring HA Proxy

To configure HA Proxy you'll need two available local ports, one for the control plane and the other for the cache
plane. You'll also need the hostnames of the actual control plane and cache plane servers that your account is
configured to connect to. If you're using a JWT to authenticate with Momento, you can extract the hostnames for the
control plane (cp) and cache plane (c) with the following shell command:

```shell
echo $MOMENTO_API_KEY | awk -F . {'print $2}' | base64 -d
```

A sample configuration (using nonexistent server hostnames) is as follows:

```text
frontend control-plane-fe
  bind localhost:4443
  option tcplog
  mode tcp
  default_backend control-plane-be

backend control-plane-be
  mode tcp
  server server1 control.some-control-cell-name.momentohq.com:443

frontend cache-plane-fe
  bind localhost:4444
  option tcplog
  mode tcp
  default_backend cache-plane-be

backend cache-plane-be
  mode tcp
  server server1 cache.some-cache-cell-name.momentohq.com:443
```

## Configuring the Momento Client

Configuring the Momento client to use the proxy requires the same information, which is passed to the credential
provider. Using the `EnvMomentoTokenProvider`, which reads the token from an environment variable:

```php
$authProvider = new EnvMomentoTokenProvider(
  envVariableName: "MOMENTO_API_KEY",
  controlEndpoint: "localhost:4443",
  cacheEndpoint: "localhost:4444",
  trustedControlEndpointCertificateName: "control.some-control-cell-name.momentohq.com",
  trustedCacheEndpointCertificateName: "cache.some-cache-cell-name.momentohq.com"
);
```

This configuration instructs the client to connect through the proxy server, **overriding the target name used for SSL
host name checking**.

----------------------------------------------------------------------------------------
For more info, visit our website at [https://gomomento.com](https://gomomento.com)!
