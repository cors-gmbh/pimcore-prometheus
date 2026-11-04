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

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

// Public repository: the shared CORS tooling (cors/dev) lives on the private Packagist, which
// GitHub Actions cannot reach from here, so the public coreshop/test-setup rule set is used.
return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import('vendor/coreshop/test-setup/ecs.php');
    $ecsConfig->parallel();
    $ecsConfig->paths(['src']);

    $header = <<<EOT
CORS GmbH

This software is available under the GNU General Public License version 3 (GPLv3).

@copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
@license    https://www.cors.gmbh/license GPLv3
EOT;

    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, ['header' => $header]);
};
