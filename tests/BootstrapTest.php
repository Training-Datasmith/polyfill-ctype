<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Ctype\Tests;

use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    private const REPO_ROOT = __DIR__.'/..';

    private function hostRepoRoot(): string
    {
        $host = getenv('POLYFILL_CTYPE_HOST_REPO');

        return false !== $host && '' !== $host ? $host : self::REPO_ROOT;
    }

    public function testPolyfillDefinesFunctionsWhenExtensionMissing(): void
    {
        if (\PHP_VERSION_ID >= 80000) {
            $payload = $this->runAlpinePhpWithoutCtype(
                'alpine:3.16@sha256:452e7292acee0ee16c332324d7de05fa2c99f9994ecc9f0779c602916a672ae4',
                'php81-cli',
                'php81',
                $this->bootstrapProbeScript()
            );
            $this->assertSame('bootstrap80.php', $payload['declaring_file']);
            $this->assertSame('mixed', $payload['param_type']);
            $this->assertSame('bool', $payload['return_type']);
        } else {
            $payload = $this->runAlpinePhpWithoutCtype(
                'alpine:3.13@sha256:469b6e04ee185740477efa44ed5bdd64a07bbdd6c7e5f5d169e540889597b911',
                'php7-cli',
                'php7',
                $this->bootstrapProbeScript()
            );
            $this->assertSame('bootstrap.php', $payload['declaring_file']);
            $this->assertNull($payload['param_type']);
            $this->assertNull($payload['return_type']);
        }

        foreach ([
            'ctype_alnum', 'ctype_alpha', 'ctype_cntrl', 'ctype_digit', 'ctype_graph',
            'ctype_lower', 'ctype_print', 'ctype_punct', 'ctype_space', 'ctype_upper', 'ctype_xdigit',
        ] as $function) {
            $this->assertTrue($payload['exists'][$function], $function.' should exist');
        }

        $this->assertFalse($payload['is_internal']);
        $this->assertTrue($payload['ctype_digit_123']);
        $this->assertFalse($payload['ctype_digit_12a']);
        $this->assertFalse($payload['ctype_digit_empty']);
        $this->assertTrue($payload['ctype_alnum_Abc']);
        $this->assertFalse($payload['ctype_alnum_AbcBang']);
        $this->assertTrue($payload['ctype_alpha_65']);
        $this->assertTrue($payload['ctype_digit_256']);
    }

    public function testBootstrapSurvivesASecondInclude(): void
    {
        $payload = $this->runPhpSubprocess('-n', <<<'PHP'
$repo = getenv('POLYFILL_CTYPE_REPO');
require $repo.'/Ctype.php';
require $repo.'/bootstrap.php';
require $repo.'/bootstrap.php';
echo json_encode(['ctype_digit_5' => ctype_digit('5')]);
PHP
        );

        $this->assertTrue($payload['ctype_digit_5']);
    }

    public function testBootstrapLeavesTheExtensionFunctionsInPlace(): void
    {
        if (!\extension_loaded('ctype')) {
            $this->markTestSkipped('The ctype extension is not loaded in this PHP build.');
        }

        $payload = $this->runPhpSubprocess('', <<<'PHP'
$repo = getenv('POLYFILL_CTYPE_REPO');
require $repo.'/Ctype.php';
require $repo.'/bootstrap.php';
$ref = new ReflectionFunction('ctype_digit');
echo json_encode([
    'is_internal' => $ref->isInternal(),
    'ctype_digit_123' => ctype_digit('123'),
    'ctype_digit_12a' => ctype_digit('12a'),
]);
PHP
        );

        $this->assertTrue($payload['is_internal']);
        $this->assertTrue($payload['ctype_digit_123']);
        $this->assertFalse($payload['ctype_digit_12a']);
    }

    private function bootstrapProbeScript(): string
    {
        return <<<'PHP'
$repo = getenv('POLYFILL_CTYPE_REPO');
require $repo.'/Ctype.php';
require $repo.'/bootstrap.php';

$functions = [
    'ctype_alnum', 'ctype_alpha', 'ctype_cntrl', 'ctype_digit', 'ctype_graph',
    'ctype_lower', 'ctype_print', 'ctype_punct', 'ctype_space', 'ctype_upper', 'ctype_xdigit',
];
$exists = [];
foreach ($functions as $name) {
    $exists[$name] = function_exists($name);
}

$ref = new ReflectionFunction('ctype_digit');
$param = $ref->getParameters()[0];
$return = $ref->getReturnType();

echo json_encode([
    'exists' => $exists,
    'is_internal' => $ref->isInternal(),
    'declaring_file' => basename($ref->getFileName()),
    'param_type' => $param->hasType() ? (string) $param->getType() : null,
    'return_type' => null !== $return ? (string) $return : null,
    'ctype_digit_123' => ctype_digit('123'),
    'ctype_digit_12a' => ctype_digit('12a'),
    'ctype_digit_empty' => ctype_digit(''),
    'ctype_alnum_Abc' => ctype_alnum('Abc'),
    'ctype_alnum_AbcBang' => ctype_alnum('Abc!'),
    'ctype_alpha_65' => ctype_alpha(65),
    'ctype_digit_256' => ctype_digit(256),
]);
PHP;
    }

    /**
     * @return array<string, mixed>
     */
    private function runAlpinePhpWithoutCtype(string $image, string $apkPackage, string $phpBinary, string $body): array
    {
        $hostRepo = $this->hostRepoRoot();
        $probeName = '.tmp-bootstrap-probe-'.md5($body).'.php';
        $scriptPath = self::REPO_ROOT.'/'.$probeName;
        file_put_contents($scriptPath, "<?php\n".$body);
        $hostScriptPath = rtrim($hostRepo, '/').'/'.$probeName;

        $dockerCmd = escapeshellarg($this->dockerBinary()).' run --rm'
            .' -e POLYFILL_CTYPE_REPO=/app'
            .' -v '.escapeshellarg($hostRepo).':/app:ro'
            .' -v '.escapeshellarg($hostScriptPath).':/probe.php:ro'
            .' '.escapeshellarg($image)
            .' sh -lc '.escapeshellarg(
                'apk add --no-cache '.escapeshellarg($apkPackage).' '.escapeshellarg(str_replace('-cli', '-json', $apkPackage)).' >/dev/null && '
                .escapeshellarg($phpBinary).' /probe.php'
            );

        $process = proc_open($dockerCmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, self::REPO_ROOT);
        $this->assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertSame(0, $exitCode, 'Alpine bootstrap probe failed: '.$stderr);
        $this->assertSame('', trim($stderr), 'Alpine bootstrap probe stderr must be empty');

        $decoded = json_decode($stdout, true);
        $this->assertIsArray($decoded, 'Alpine bootstrap probe returned invalid JSON: '.$stdout);

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function runPhpSubprocess(string $iniFlags, string $body): array
    {
        $scriptPath = self::REPO_ROOT.'/.tmp-php-subprocess-'.md5($body).'.php';
        file_put_contents($scriptPath, "<?php\n".$body);

        $cmd = escapeshellarg(PHP_BINARY);
        if ('' !== $iniFlags) {
            $cmd .= ' '.escapeshellarg($iniFlags);
        }
        $cmd .= ' '.escapeshellarg($scriptPath);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = $_ENV;
        $env['POLYFILL_CTYPE_REPO'] = self::REPO_ROOT;

        $process = proc_open($cmd, $descriptors, $pipes, self::REPO_ROOT, $env);
        $this->assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertSame(0, $exitCode, 'Subprocess failed: '.$stderr);
        $this->assertSame('', $stderr, 'Subprocess stderr must be empty');

        $decoded = json_decode($stdout, true);
        $this->assertIsArray($decoded, 'Subprocess returned invalid JSON: '.$stdout);

        return $decoded;
    }

    private function dockerBinary(): string
    {
        $cached = $this->hostRepoRoot().'/.docker-static/bin/docker';
        $runtime = '/tmp/polyfill-ctype-docker-cli';
        if (is_executable($runtime)) {
            return $runtime;
        }

        if (!is_readable('/var/run/docker.sock')) {
            $this->fail('Docker socket is required to probe bootstrap without the ctype extension.');
        }

        $hostRepo = $this->hostRepoRoot();
        $archive = $hostRepo.'/.docker-static/docker-static-27.5.1.tgz';
        if (!is_dir(dirname($archive))) {
            mkdir(dirname($archive), 0755, true);
        }
        if (!is_file($archive)) {
            $contents = file_get_contents('https://download.docker.com/linux/static/stable/x86_64/docker-27.5.1.tgz');
            $this->assertNotFalse($contents, 'Unable to download a static Docker client.');
            file_put_contents($archive, $contents);
        }

        $extractDir = $hostRepo.'/.docker-static/extract';
        exec('rm -rf '.escapeshellarg($extractDir));
        mkdir($extractDir, 0755, true);
        $command = 'tar -xzf '.escapeshellarg($archive).' -C '.escapeshellarg($extractDir).' docker/docker';
        exec($command, $output, $exitCode);
        $this->assertSame(0, $exitCode, 'Unable to extract static Docker client.');
        if (!is_dir(dirname($cached))) {
            mkdir(dirname($cached), 0755, true);
        }
        if (is_file($cached)) {
            unlink($cached);
        }
        rename($extractDir.'/docker/docker', $cached);
        chmod($cached, 0755);

        copy($cached, $runtime);
        chmod($runtime, 0755);

        return $runtime;
    }
}
