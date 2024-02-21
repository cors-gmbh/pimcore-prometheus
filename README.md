![CORS.gmbh](https://github.com/cors-gmbh/.github/raw/main/cors-we-want-you-3.jpg)


CORS Pimcore Prometheus
--------

Expose Pimcore Metrics to Prometheus. This includes the following metrics:

 - Installed Bundles and Versions
 - Pimcore Sites
 - Tables and row Counts
 - Symfony Messenger Processed Messages Count

### Installation

1. Install and enable the bundle
```bash
composer require cors/prometheus
bin/console pimcore:bundle:enable CORSPrometheusBundle
```

2. Configure the routes

```yaml
# app/routes.yaml

_cors_prometheus:
  resource: "@CORSPrometheusBundle/Resources/config/routing.yaml"
```

Make sure to not expose the `/metrics` route to public access! We do that with nginx configs:

```nginx
location ~* /metrics {
  deny all;
  return 403;
}
```

You can also add a event listener that uses a Key to protect the route:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class MetricsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'request',
        ];
    }

    public function request(KernelEvent $event): void
    {
        if ('cors_prometheus' === $event->getRequest()->attributes->get('_route')) {
            if ($event->getRequest()->query->get('apiKey') !== 'your-super-secret-key') {
                throw new NotFoundHttpException('Access denied');
            }
        }
    }
}
```

### Storage

Also make sure you have some kind of storage to temporarily store the metrics. We use a Redis instance for that.

```yaml
    Prometheus\Storage\Adapter:
        factory: [ 'CORS\Bundle\PrometheusBundle\StorageFactory', 'create' ]
        arguments:
            $dsn: 'redis://redis:6379'

```