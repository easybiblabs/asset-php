<?php
/**
 * Imagine Easy Solutions LLC Copyright 2013-2015
 * MIT License, see the file called LICENSE for details.
 *
 * @author   Richard Wossal <richard.wossal@imagineeasy.com>
 */
namespace EasyBib;

use Composer\Script\Event;
use Composer\Util\Filesystem as FileUtil;

/**
 * Asset - Cache busting for asset files.
 * @author Richard Wossal <richard@lagged.biz>
 */
class Asset
{
    private static $event;

    /**
     * Prefix the files with the md5 hash.
     */
    public static function run(Event $event)
    {
        self::$event = $event;
        $config = $event->getComposer()->getConfig();
        $assetConfig = $config->get('asset');

        try {

            $sourcePath = $assetConfig['sourcePath'];
            $targetPath = $assetConfig['targetPath'];

            $fileUtil = new FileUtil();
            $fileUtil->ensureDirectoryExists($sourcePath);
            $fileUtil->ensureDirectoryExists($targetPath);

            self::out('Asset: Copying files');
            self::out("       from $sourcePath to $targetPath");
            $prefixedFiles  = self::copyFiles($targetPath, $sourcePath, $assetConfig['files']);
            self::out('Asset: Dumping assets file map');
            self::dumpFileMap($prefixedFiles);
            self::out('Asset: Done');

        } catch (\Exception $e) {

            // We fail silently in order to not break the install/deployment.
            // The app will still work, even if the compiled files aren't there.
            self::out('Asset: Error: ' . $e->getMessage());

        }
    }

    /**
     * Get the path of the compiled file.
     * Uses the file written in dumpFileMap() below.
     * Called by the app at runtime.
     *
     * @param string $sourceFileName (something like "/js/foo.js" or "/css/bar.css")
     * @return string
     */
    public static function path($sourceFileName)
    {
        $assetFileMap = apc_fetch('asset-file-map');
        if (!is_array($assetFileMap)) {
            if (is_file(__DIR__ . '/asset-file-map.php')) {
                $assetFileMap = @include __DIR__ . '/asset-file-map.php';
                apc_store('asset-file-map', $assetFileMap);
            }
        }

        if (is_array($assetFileMap) && array_key_exists($sourceFileName, $assetFileMap)) {
            return $assetFileMap[$sourceFileName];
        }

        return $sourceFileName . '?ts=' . time(); // fallback to source + cache-busting
    }

    private static function out($s)
    {
        error_log($s);
        //self::$event->getIO()->write($s);
    }

    /**
     * This var_exports a mapping of source-filename to compiled-filename.
     * The file will look roughly like this:
     *
     * <?php return array (
     *     '/js/easybib.autocite.js' => '/js/13a2551b6a33d23c7a76c591991219c8-easybib.autocite.js',
     *     '/js/easybib.contact.js' => '/js/6e268a4780e45c6c41ea2daae22db044-easybib.contact.js',
     *     ...
     * );
     *
     * @param array array($source_name => $compiled_name)
     */
    private static function dumpFileMap($map)
    {
        $name = __DIR__ . '/asset-file-map.php';
        self::out("\t$name");
        $dumpCode = sprintf('<?php return %s;', var_export($map, true));
        file_put_contents($name, $dumpCode);
    }


    /**
     * @param string $targetPath
     * @param string $sourcePath
     * @param array  $files
     *
     * @return map of (original name => hash-prefixed name)
     */
    private static function copyFiles($targetPath, $sourcePath, $files) {
        $prefixedNames = array();
        foreach ($files as $sourceName) {

            $prefixedName = sprintf(
                '%s/%s-%s',
                dirname($sourceName),
                md5_file($sourcePath . $sourceName),
                basename($sourceName)
            );
            copy($sourcePath . $sourceName, $targetPath . '/' . $prefixedName);
            self::out(sprintf("\t% -30s -> %s", $sourceName, $prefixedName));
            $prefixedNames[$sourceName] = '/' . $prefixedName;
        }
        return $prefixedNames;
    }
}