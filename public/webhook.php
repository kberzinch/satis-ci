<?php declare(strict_types = 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$payload = payload();

switch ($_SERVER['HTTP_X_GITHUB_EVENT']) {
    case 'ping':
        echo 'Hello GitHub!';
        exit;
    case 'release':
        $repo = $payload['repository']['name'];
        if ('published' !== $payload['action']) {
            echo 'Action is ' . $payload['action'] . ', not running build';
            exit;
        }

        $return_value = 0;

        $logfile = __DIR__ . '/' . $repo . '/' . $payload['release']['id'] . '.txt';

        mkdir(__DIR__ . '/' . $repo, 0700, true);

        file_put_contents(
            $logfile,
            'Delivery ID:    ' . $_SERVER['HTTP_X_GITHUB_DELIVERY'] . "\n" .
            'Release ID:     ' . $payload['release']['id'] . "\n" .
            'Repository:     ' . $payload['repository']['full_name'] . "\n",
            FILE_APPEND
        );

        echo 'OK, log file created at ' . $logfile;

        $satis_dot_json = file_get_contents(__DIR__ . '/../satis.json');

        if (false === $satis_dot_json) {
            file_put_contents($logfile, "\n# satis.json does not exist or is unreadable", FILE_APPEND);
            $log = file_get_contents($logfile);
            if (false === $log) {
                $log = 'Could not read log file.';
            }
            if (isset($email_from, $email_to)) {
                mail(
                    $email_to,
                    '[' . $payload['repository']['full_name'] . '] Release publication failed',
                    $log,
                    'From: ' . $email_from
                );
            }
            exit;
        }

        $satis_dot_json = json_decode($satis_dot_json);

        if (null === $satis_dot_json) {
            file_put_contents($logfile, "\n# satis.json does not contain valid JSON", FILE_APPEND);
            $log = file_get_contents($logfile);
            if (false === $log) {
                $log = 'Could not read log file.';
            }
            if (isset($email_from, $email_to)) {
                mail(
                    $email_to,
                    '[' . $payload['repository']['full_name'] . '] Release publication failed',
                    $log,
                    'From: ' . $email_from
                );
            }
            exit;
        }

        $repo_is_added = false;

        foreach ($satis_dot_json->repositories as $repository) {
            if ($repository->url === $payload['repository']['clone_url']) {
                $repo_is_added = true;
                break;
            }
        }

        if (!$repo_is_added) {
            file_put_contents($logfile, "\n# satis.json does not have this repository configured\n", FILE_APPEND);
            passthru(
                '/bin/bash -x -e -o pipefail ' . __DIR__ . '/../add.sh ' . $payload['repository']['clone_url'] . ' >> '
                    . $logfile . ' 2>&1',
                $return_value
            );
            if (0 !== $return_value) {
                $log = file_get_contents($logfile);
                if (false === $log) {
                    $log = 'Could not read log file.';
                }
                if (isset($email_from, $email_to)) {
                    mail(
                        $email_to,
                        '[' . $payload['repository']['full_name'] . '] Release publication failed',
                        $log,
                        'From: ' . $email_from
                    );
                }
                die;
            }
        }

        $composer_auth_blob = json_encode(
            [
                'http-basic' => [
                    which_github() => [
                        'username' => 'x-access-token',
                        'password' => token(),
                    ],
                ],
            ]
        );

        putenv('COMPOSER_AUTH=' . $composer_auth_blob);

        file_put_contents($logfile, "\n# Running satis build\n", FILE_APPEND);

        passthru(
            '/bin/bash -x -e -o pipefail ' . __DIR__ . '/../build.sh ' . $payload['repository']['clone_url'] . ' >> '
                . $logfile . ' 2>&1',
            $return_value
        );

        if (0 !== $return_value) {
            $log = file_get_contents($logfile);
            if (false === $log) {
                $log = 'Could not read log file.';
            }
            if (isset($email_from, $email_to)) {
                mail(
                    $email_to,
                    '[' . $payload['repository']['full_name'] . '] Release publication failed',
                    $log,
                    'From: ' . $email_from
                );
            }
            die;
        }
        exit;
    default:
        echo 'Unrecognized event ' . $_SERVER['HTTP_X_GITHUB_EVENT'];
        exit;
}
