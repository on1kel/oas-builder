<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Profile31\Profile\OAS31Profile;
use On1kel\OAS\Core\Version\ProfileRegistry;

$globalProfiles = new ProfileRegistry();
$globalProfiles->register(new OAS31Profile());

// 2) Профиль по умолчанию для fluent-слоя (FeatureGuard/UsesProfile)
ProfileProvider::setDefault(new OAS31Profile());