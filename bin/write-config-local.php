<?php

// Writes app/config/config.local.neon from the current environment - used by
// the deploy workflow both for the CI-local smoke-test config (pointed at the
// job's own MySQL service container) and for the real production config
// (pointed at the actual hosting secrets), so both share one encoding path.
// See the modernize-nette-app skill's "env-var/secrets precedence" note:
// bootstrap.php merges this file's `parameters: env: {...}` with the bare
// getenv() dump, so a missing/empty secret here doesn't clobber the other.

require __DIR__ . '/../vendor/autoload.php';

$env = [
	'DB_DSN' => getenv('DB_DSN'),
	'DB_USER' => getenv('DB_USER'),
	'DB_PASSWORD' => getenv('DB_PASSWORD'),
	'GOOGLE_MAPS_API_KEY' => getenv('GOOGLE_MAPS_API_KEY'),
	'EMAIL_SERVER' => getenv('EMAIL_SERVER'),
	'EMAIL_PORT' => (int) getenv('EMAIL_PORT'),
	'EMAIL_ARGUMENTS' => getenv('EMAIL_ARGUMENTS'),
	'EMAIL_USER' => getenv('EMAIL_USER'),
	'EMAIL_PASSWORD' => getenv('EMAIL_PASSWORD'),
];

file_put_contents(
	__DIR__ . '/../app/config/config.local.neon',
	Nette\Neon\Neon::encode(['parameters' => ['env' => $env]], true)
);
