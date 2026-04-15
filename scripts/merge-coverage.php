<?php

declare(strict_types=1);

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Clover;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlFacade;
use SebastianBergmann\CodeCoverage\Report\Text;
use SebastianBergmann\CodeCoverage\Report\Thresholds;

require __DIR__.'/../vendor/autoload.php';

if ($argc < 4) {
    fwrite(STDERR, "Uso: php scripts/merge-coverage.php <unitario.cov> <integracao.cov> <saida-dir>\n");
    exit(1);
}

[$script, $unitarioFile, $integracaoFile, $outputDir] = $argv;

function carregarCobertura(string $path): CodeCoverage
{
    if (! is_file($path)) {
        throw new RuntimeException("Arquivo de cobertura não encontrado: {$path}");
    }

    $coverage = require $path;

    if (! $coverage instanceof CodeCoverage) {
        throw new RuntimeException("Arquivo inválido de cobertura: {$path}");
    }

    return $coverage;
}

function garantirDiretorio(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (! mkdir($path, 0777, true) && ! is_dir($path)) {
        throw new RuntimeException("Não foi possível criar o diretório: {$path}");
    }
}

try {
    $unitario = carregarCobertura($unitarioFile);
    $integracao = carregarCobertura($integracaoFile);

    $unitario->merge($integracao);

    garantirDiretorio($outputDir);

    $combinedCov = $outputDir.'/combined.cov';
    $combinedTxt = $outputDir.'/coverage.txt';
    $combinedXml = $outputDir.'/clover.xml';
    $combinedHtml = $outputDir.'/html';

    file_put_contents($combinedCov, serialize($unitario));

    $textReport = (new Text(Thresholds::default()))->process($unitario, true);
    file_put_contents($combinedTxt, $textReport);

    (new Clover)->process($unitario, $combinedXml, 'NVSL_BACKEND');
    (new HtmlFacade('NVSL_BACKEND'))->process($unitario, $combinedHtml);

    fwrite(STDOUT, $textReport);
    fwrite(STDOUT, PHP_EOL."Artefatos gerados em: {$outputDir}".PHP_EOL);
} catch (Throwable $e) {
    fwrite(STDERR, '[coverage-merge] '.$e->getMessage().PHP_EOL);
    exit(1);
}
