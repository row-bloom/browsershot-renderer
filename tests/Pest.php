<?php

use RowBloom\BrowsershotRenderer\BrowsershotRenderer;
use RowBloom\RowBloom\RowBloomServiceProvider;
use RowBloom\RowBloom\Support;

app()->make(RowBloomServiceProvider::class)->register();
app()->make(RowBloomServiceProvider::class)->boot();

app()->make(Support::class)->registerRendererDriver(BrowsershotRenderer::NAME, BrowsershotRenderer::class);
