[![CORS - One system. Down to the Core.](https://raw.githubusercontent.com/cors-gmbh/.github/refs/heads/main/cors-banner.jpg)](https://cors.gmbh)

CORS Prometheus Bundle
--------

Exposes Pimcore metrics to Prometheus:

 - installed bundles and their versions
 - Pimcore sites
 - database tables and their row counts, the Pimcore and the MySQL version
 - processed and failed Symfony Messenger messages

> **Branch 2026.x is Pimcore 2026.x.** For Pimcore 11 and 12 use branch `12.x`.

# Installation

## 1. Require the bundle

```bash
composer require cors/prometheus
```

## 2. `config/bundles.php` — register the bundle

```php
return [
    // …
    \CORS\Bundle\PrometheusBundle\CORSPrometheusBundle::class => ['all' => true],
];
```

## 3. Import the route

```yaml
# config/routes.yaml
_cors_prometheus:
    resource: "@CORSPrometheusBundle/Resources/config/routing.yaml"
```

This registers the route `cors_prometheus` at `/metrics`. The bundle never registers it on its
own, so the endpoint only exists in projects that import it.

## 4. Configure a storage

The bundle defaults to `Prometheus\Storage\InMemory`. That is enough for the metrics collected
during the `/metrics` request itself (bundles, sites, tables), but the Messenger counters are
written by the worker processes and are lost with them. Point the storage at a Redis instance
shared by the web and the worker containers:

```yaml
# config/services.yaml
services:
    Prometheus\Storage\Adapter:
        factory: [ 'CORS\Bundle\PrometheusBundle\StorageFactory', 'create' ]
        arguments:
            $dsn: 'redis://redis:6379'
```

`StorageFactory` accepts `redis:` DSNs only (anything Symfony's `RedisAdapter` understands,
e.g. `redis://redis:6379?auth=password`) and prefixes all keys with `prom_`.

# Access control

`/metrics` has no authentication of its own. Make sure it is not reachable from the public,
for example in nginx:

```nginx
location ~* /metrics {
    deny all;
    return 403;
}
```

Or protect it with a key that your Prometheus scraper sends along:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class MetricsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'request',
        ];
    }

    public function request(RequestEvent $event): void
    {
        if ('cors_prometheus' !== $event->getRequest()->attributes->get('_route')) {
            return;
        }

        if ($event->getRequest()->query->get('apiKey') !== 'your-super-secret-key') {
            throw new NotFoundHttpException();
        }
    }
}
```

# Metrics

All gauges live in the namespace `cors_pimcore` and carry the labels `env` (the kernel
environment), `exporter` (`cors`) and `type`, plus the labels listed here:

| Metric | Labels | Source |
| --- | --- | --- |
| `cors_pimcore_pimcore_version` | `version` | `PimcoreMetrics` |
| `cors_pimcore_mysql_version` | `version` | `PimcoreMetrics` |
| `cors_pimcore_table_<table>` (value: row count) | — | `PimcoreMetrics` |
| `cors_pimcore_bundle_<bundle>` | `name`, `version`, `class`, `installed`, `composer_package`, `composer_version`, … | `BundleMetrics` |
| `cors_pimcore_site_<main_domain>` | `main_domain`, `domains`, `root_document`, … | `SitesMetrics` |

The row counts come from `information_schema.TABLES` and are estimates for InnoDB tables.

## Messenger

`PrometheusMiddleware` counts every message a worker receives, per bus: `<bus>_message` and, for
messages whose handler throws, `<bus>_message_error`, both with the labels `message` (class
name) and `label` (short class name). Dots and dashes in the bus name become underscores.

The bundle adds the middleware to the buses `coreshop.bus` and `messenger.bus.pimcore-core`.
For any other bus, add it in your messenger configuration:

```yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - 'CORS\Bundle\PrometheusBundle\Messenger\PrometheusMiddleware'
```

## Custom metrics

Implement `CORS\Bundle\PrometheusBundle\Metrics\MetricsCollectorInterface`, return a list of
`Metric` objects from `collect()` and tag the service:

```yaml
services:
    App\Metrics\OrderMetrics:
        tags:
            - { name: cors.metrics_collector, type: orders }
```

# Development

The repository doubles as a runnable Pimcore application on **Pimcore 2026 with Studio** (the
CORS bundle template: `Kernel.php`, `bin/console`, `config/`, `dev/`, `docker-compose.yaml`
including the shared `dev-compose` stack). The bundle itself is `src/`
(`CORSPrometheusBundle::getPath()`, with `Resources/` inside); everything else at the repository
root only serves the dev harness or the CI, and `.gitattributes` keeps it out of the distributed
composer package.

```bash
docker compose up -d
docker compose exec -T php composer install
docker compose exec -T php vendor/bin/pimcore-install \
    --install-profile='App\InstallProfile\StudioInstallProfile' \
    --admin-username=admin --admin-password=admin --no-interaction
docker compose exec -T php bin/console cache:clear
```

The metrics are then served at `https://cors-pimcore-prometheus.dev.localhost/metrics`, Studio
at `https://cors-pimcore-prometheus.dev.localhost/pimcore-studio/`. The harness imports the
bundle routes in `config/routes/cors_prometheus.yaml` and stores the metrics in the dev-compose
Redis (`config/services.yaml`).

The install profile (`dev/InstallProfile/StudioInstallProfile.php`) declares the bundles and
infrastructure the harness needs (Studio backend/UI, generic data index + OpenSearch, Mercure,
Doctrine messenger transport). Connection defaults point at the dev-compose services and live
in `.env`; the Pimcore bundles are registered in `config/bundles.php`, the bundle under
development in `Kernel.php`.

Pimcore 2026 refuses to boot without a registered instance: the instance identifier
(`cors-pimcore-prometheus`) is committed in `.env`, and `PIMCORE_ENCRYPTION_SECRET` plus
`PIMCORE_PRODUCT_KEY` go into your uncommitted `.env.local`. Register the identifier at
[license.pimcore.com](https://license.pimcore.com) to obtain them; CI reads the same three
values from the repository secrets `PIMCORE_ENCRYPTION_SECRET`, `PIMCORE_INSTANCE_IDENTIFIER`
and `PIMCORE_PRODUCT_KEY` (`.github/workflows/static.yaml`, written to `.env.local` by the
shared `php-test` workflow before the kernel boots).

The files marked "ZENTRAL VERWALTETE DATEI" (`docker-compose.yaml`, `bin/console`, most of
`config/`, `.github/workflows/static.yaml`) are synced from `cors-gmbh/shared-workflows-private`;
repository-specific additions belong in `compose.override.yaml`, `config/local/` or separate files.

Static checks run the same way as in CI. This repository is public, so the shared rule set comes
from the public `coreshop/test-setup` package instead of the private `cors/dev`:

```bash
vendor/bin/ecs check src
vendor/bin/phpstan analyse
vendor/bin/psalm
```

## License

[MIT](https://opensource.org/license/mit), see [LICENSE.md](LICENSE.md).
