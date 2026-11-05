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
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\DependencyInjection\Container;

final class BundleMetrics implements MetricsCollectorInterface
{
    public function __construct(
        protected PimcoreBundleManager $bundleManager,
    ) {
    }

    #[\Override]
    public function collect(): array
    {
        $metrics = [];
        $packagePaths = $this->getPackagePaths();

        /** @psalm-suppress InternalMethod **/
        foreach ($this->bundleManager->getActiveBundles() as $bundle) {
            $composerPackage = $this->findComposerPackage($bundle, $packagePaths);
            $composerVersion = null;
            $version = null;

            if ($composerPackage !== null) {
                $composerVersion = InstalledVersions::getVersion($composerPackage);
                $version = InstalledVersions::getPrettyVersion($composerPackage);
            }

            $name = Container::underscore($bundle->getName());

            /** @psalm-suppress InternalMethod **/
            $metrics[] = new Metric(
                'bundle_' . $name,
                [
                    'type' => 'pimcore_bundle',
                    'name' => $name,
                    'version' => $version,
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

    /**
     * Install paths of all installed composer packages, the deepest first.
     *
     * The root package is installed at the project root, which contains every other package, so a
     * bundle belongs to the package with the longest matching path rather than the first one.
     * Pimcore's PackageVersionTrait::getComposerPackageName() takes the first match and therefore
     * reports the root package for every bundle that uses the trait.
     *
     * @return array<string, string> package name => real install path
     */
    private function getPackagePaths(): array
    {
        $paths = [];

        foreach (InstalledVersions::getAllRawData() as $installed) {
            foreach ($installed['versions'] as $packageName => $package) {
                // replaced or provided (virtual) packages have no install path
                if (!isset($package['install_path'])) {
                    continue;
                }

                $path = realpath($package['install_path']);

                if ($path !== false) {
                    $paths[$packageName] = $path;
                }
            }
        }

        uasort($paths, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $paths;
    }

    /**
     * @param array<string, string> $packagePaths
     */
    private function findComposerPackage(PimcoreBundleInterface $bundle, array $packagePaths): ?string
    {
        $file = (new \ReflectionClass($bundle))->getFileName();

        if ($file === false) {
            return null;
        }

        $file = realpath($file) ?: $file;

        foreach ($packagePaths as $packageName => $path) {
            if (str_starts_with($file, $path . \DIRECTORY_SEPARATOR)) {
                return $packageName;
            }
        }

        return null;
    }

    private function getShortClassName(string $className): string
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist', $className));
        }

        $parts = explode('\\', $className);

        return array_pop($parts);
    }
}
