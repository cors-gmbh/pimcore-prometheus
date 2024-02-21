<?php

declare(strict_types=1);

/*
 * CORS GmbH
 *
 * This software is available under the GNU General Public License version 3 (GPLv3).
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    https://www.cors.gmbh/license GPLv3
 */

namespace CORS\Bundle\PrometheusBundle\Metrics;

use Composer\InstalledVersions;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\DependencyInjection\Container;

class BundleMetrics implements MetricsCollectorInterface
{
    public function __construct(
        protected PimcoreBundleManager $bundleManager,
    ) {
    }

    public function collect(): array
    {
        $metrics = [];
        $composerJson = PIMCORE_PROJECT_ROOT . '/vendor/composer/installed.json';
        $composerPackages = [];

        if (!file_exists($composerJson)) {
            return [];
        }

        $composerJsonContent = file_get_contents($composerJson);

        if ($composerJsonContent === false) {
            return [];
        }

        /**
         * @var array $composerPackages
         * @phpstan-ignore-next-line
         */
        $composerPackages = json_decode($composerJsonContent, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($composerPackages['packages'])) {
            return [];
        }

        /** @psalm-suppress InternalMethod **/
        foreach ($this->bundleManager->getActiveBundles() as $bundle) {
            $composerVersion = null;
            $composerPackage = null;

            if (method_exists($bundle, 'getComposerPackageName')) {
                $reflection = new \ReflectionClass($bundle);
                $method = $reflection->getMethod('getComposerPackageName');
                $method->setAccessible(true);

                /**
                 * @var string $composerPackage
                 */
                $composerPackage = $method->invoke($bundle);

                try {
                    $composerVersion = InstalledVersions::getVersion($composerPackage);
                } catch (\Exception $ex) {
                    //Ignore Exception
                }
            } else {
                foreach ($composerPackages['packages'] as $package) {
                    if (!isset($package['extra'])) {
                        continue;
                    }

                    if (!isset($package['extra']['pimcore'])) {
                        continue;
                    }

                    if (!isset($package['extra']['pimcore']['bundles'])) {
                        continue;
                    }

                    foreach ($package['extra']['pimcore']['bundles'] as $pimcoreBundle) {
                        if ($pimcoreBundle === get_class($bundle)) {
                            $composerPackage = $package['name'];
                            $composerVersion = $package['version_normalized'];

                            break 2;
                        }
                    }
                }
            }

            $name = Container::underscore($bundle->getName());

            /** @psalm-suppress InternalMethod **/
            $metrics[] = new Metric(
                'bundle_' . $name,
                [
                    'type' => 'pimcore_bundle',
                    'name' => $name,
                    'version' => $composerVersion ? $bundle->getVersion() : null,
                    'description' => $bundle->getDescription(),
                    'class' => get_class($bundle),
                    'short_name' => $this->getShortClassName(get_class($bundle)),
                    'installed' => $this->bundleManager->isInstalled($bundle),
                    'can_be_installed' => $this->bundleManager->canBeInstalled($bundle),
                    'can_be_uninstalled' => $this->bundleManager->canBeUninstalled($bundle),
                    'can_be_updated' => method_exists(
                        $this->bundleManager,
                        'canBeUpdated',
                    ) ? $this->bundleManager->canBeUpdated($bundle) : false,
                    'composer_package' => $composerPackage,
                    'composer_version' => $composerVersion,
                    'exporter' => 'cors',
                ],
                'Bundle ' . $bundle->getNiceName(),
            );
        }

        return $metrics;
    }

    protected function getShortClassName(string $className): string
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist', $className));
        }

        $parts = explode('\\', $className);

        return array_pop($parts);
    }
}
