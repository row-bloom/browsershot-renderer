<?php

namespace RowBloom\BrowsershotRenderer;

use RowBloom\RowBloom\Config;
use RowBloom\RowBloom\Fs\File;
use RowBloom\RowBloom\Options;
use RowBloom\RowBloom\Renderers\Contract as RenderersContract;
use RowBloom\RowBloom\Renderers\Sizing\LengthUnit;
use RowBloom\RowBloom\Renderers\Sizing\Margin;
use RowBloom\RowBloom\Types\Css;
use RowBloom\RowBloom\Types\Html;
use Spatie\Browsershot\Browsershot;

class BrowsershotRenderer implements RenderersContract
{
    public const NAME = 'Browsershot';

    protected string $rendering;

    protected Html $html;

    protected Css $css;

    protected Options $options;

    public function __construct(protected ?Config $config = null)
    {
    }

    public function get(): string
    {
        return $this->rendering;
    }

    public function save(File $file): bool
    {
        return $file->mustBeExtension('pdf')
            ->startSaving()
            ->streamFilterAppend('convert.base64-decode')
            ->save($this->rendering);
    }

    public function render(Html $html, Css $css, Options $options, Config $config = null): static
    {
        $this->html = $html;
        $this->css = $css;
        $this->options = $options;
        $this->config = $config ?? $this->config;

        [$paperWidth, $paperHeight] = $this->options->resolvePaperSize(LengthUnit::MILLIMETER_UNIT);

        $margin = Margin::fromOptions($this->options)->allRawIn(LengthUnit::MILLIMETER_UNIT);

        $browsershot = Browsershot::html($this->html())
            ->newHeadless()
            ->paperSize($paperWidth, $paperHeight)->landscape(false)
            ->margins($margin['marginTop'], $margin['marginRight'], $margin['marginBottom'], $margin['marginLeft'])
            ->showBackground($this->options->printBackground)
            ->scale(1);

        if ($this->options->displayHeaderFooter) {
            $browsershot->showBrowserHeaderAndFooter()
                ->headerHtml($this->options->headerTemplate ?? '')
                ->footerHtml($this->options->footerTemplate ?? '');
        }

        $chromePath = $this->config?->getDriverConfig(BrowsershotConfig::class)?->chromePath;
        $nodeBinaryPath = $this->config?->getDriverConfig(BrowsershotConfig::class)?->nodeBinaryPath;
        $npmBinaryPath = $this->config?->getDriverConfig(BrowsershotConfig::class)?->npmBinaryPath;
        $nodeModulesPath = $this->config?->getDriverConfig(BrowsershotConfig::class)?->nodeModulesPath;

        if (! is_null($chromePath)) {
            $browsershot->setChromePath($chromePath);
        }

        if (! is_null($nodeBinaryPath)) {
            $browsershot->setNodeBinary($nodeBinaryPath);
        }

        if (! is_null($npmBinaryPath)) {
            $browsershot->setNpmBinary($npmBinaryPath);
        }

        if (! is_null($nodeModulesPath)) {
            $browsershot->setNodeModulePath($nodeModulesPath);
        }

        $this->rendering = $browsershot->base64pdf();

        return $this;
    }

    public static function getOptionsSupport(): array
    {
        return [
            'displayHeaderFooter' => true,
            'headerTemplate' => true,
            'footerTemplate' => true,
            'printBackground' => true,
            'preferCssPageSize' => true,
            'landscape' => true,
            'format' => true,
            'width' => true,
            'height' => true,
            'margin' => true,
            'metadataTitle' => false,
            'metadataAuthor' => false,
            'metadataCreator' => false,
            'metadataSubject' => false,
            'metadataKeywords' => false,
        ];
    }

    // ============================================================
    // Html
    // ============================================================

    private function html(): string
    {
        // ? same happens in HtmlRenderer
        return <<<_HTML
            <!DOCTYPE html>
            <head>
                <style>
                    $this->css
                </style>
                <title>Row bloom</title>
            </head>
            <body>
                $this->html
            </body>
            </html>
        _HTML;
    }
}
