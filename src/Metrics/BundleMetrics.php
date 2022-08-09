<?php

declare(strict_types=1);

/**
 * CORS GmbH.
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    https://www.cors.gmbh/license     GPLv3 and PCL
 */

namespace CORS\Bundle\PrometheusBundle\Metrics;

use Composer\InstalledVersions;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\DependencyInjection\Container;

class BundleMetrics implements MetricsCollectorInterface
{
    public function __construct(protected PimcoreBundleManager $bundleManager)
    {
    }

    public function collect(): array
    {
        $metrics = [];
        $composerJson = PIMCORE_PROJECT_ROOT.'/vendor/composer/installed.json';
        $composerPackages = [];

        if (file_exists($composerJson)) {
            $composerPackages = json_decode(file_get_contents($composerJson), true);
        }

        foreach ($this->bundleManager->getAvailableBundles() as $bundleClass) {
            $enabled = $this->bundleManager->isEnabled($bundleClass);

            /** @var PimcoreBundleInterface $bundle */
            $bundle = null;

            if ($enabled) {
                $bundle = $this->bundleManager->getActiveBundle($bundleClass, false);
            }

            $composerVersion = null;
            $composerPackage = null;

            if ($enabled && method_exists($bundle, 'getComposerPackageName')) {
                $reflection = new \ReflectionClass($bundle);
                $method = $reflection->getMethod('getComposerPackageName');

                $method->setAccessible(true);
                $composerPackage = $method->invoke($bundle);

                try {
                    $composerVersion = InstalledVersions::getVersion($composerPackage);
                } catch (\Exception $ex) {
                    //Ignore Exception
                }

                $method->setAccessible(false);
            } else {
                foreach ($composerPackages as $package) {
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
                        if ($pimcoreBundle === $bundleClass) {
                            $composerPackage = $package['name'];
                            $composerVersion = $package['version_normalized'];
                            break;
                        }
                    }
                }
            }

            if (!$enabled) {
                continue;
            }

            $name = Container::underscore($bundle->getName());
            $metrics[] = new Metric(
                'bundle_'.$name,
                [
                    'type' => 'pimcore_bundle',
                    'version' => $bundle->getVersion(),
                    'description' => $bundle->getDescription(),
                    'class' => $bundleClass,
                    'short_name' => $this->getShortClassName($bundleClass),
                    'installed' => $this->bundleManager->isInstalled($bundle),
                    'can_be_installed' => $this->bundleManager->canBeInstalled($bundle),
                    'can_be_uninstalled' => $this->bundleManager->canBeUninstalled($bundle),
                    'can_be_updated' => method_exists(
                        $this->bundleManager,
                        'canBeUpdated'
                    ) ? $this->bundleManager->canBeUpdated($bundle) : false,
                    'state' => http_build_query($this->bundleManager->getState($bundle), '', ', '),
                    'composer_package' => $composerPackage,
                    'composer_version' => $composerVersion,
                    'exporter' => 'cors',
                ],
                'Bundle '.$bundle->getNiceName()
            );
        }

        return $metrics;
    }

    protected function getShortClassName(string $className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist', $className));
        }

        $parts = explode('\\', $className);

        return array_pop($parts);
    }
}
