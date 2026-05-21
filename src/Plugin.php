<?php
/**
 * Release-Please Test plugin for Craft CMS 5.x
 *
 * Throwaway plugin for verifying release-please configuration and
 * Craft Plugin Store changelog format compliance.
 *
 * @link      https://lindemannrock.com
 * @copyright Copyright (c) 2026 LindemannRock
 */

namespace lindemannrock\releaseplease\test;

use craft\base\Plugin as BasePlugin;

class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = false;
    public bool $hasCpSection = false;
}
