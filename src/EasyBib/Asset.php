<?php
/**
 * Imagine Easy Solutions LLC Copyright 2013-2015
 * MIT License, see the file called LICENSE for details.
 *
 * @author Richard Wossal <richard.wossal@imagineeasy.com>
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
     * Prefix the files with the md5 hash. Run as a composer script.
     */
    public static function run(Event $event)
    {
        self::$event = $event;
        $assetConfig = $event->getComposer()->getConfig()->get('asset');

        self::out('Asset: Copying files');
        $prefixedFiles = [];
        foreach ($assetConfig as $nameUsedByApp => $moveConfig) {
            try {

                $sourcePath = $moveConfig['from'];
                $targetPath = $moveConfig['to'];

                $fileUtil = new FileUtil();
                $fileUtil->ensureDirectoryExists($sourcePath);
                $fileUtil->ensureDirectoryExists($targetPath);

                self::out("       $nameUsedByApp from $sourcePath to $targetPath");
                $prefixedFiles[$nameUsedByApp] = self::copyFile($nameUsedByApp, $sourcePath, $targetPath);

            } catch (\Exception $e) {

                // We fail silently in order to not break the install/deployment.
                // The app will still work, even if the compiled files aren't there.
                self::out('Asset: Error: ' . $e->getMessage());

            }
        }
        self::out('Asset: Dumping assets file map');
        self::dumpFileMap($prefixedFiles);
        self::out('Asset: Done');
    }

    /**
     * Delete the file map, fall back to source files again.
     */
    public static function clear()
    {
        apc_delete('asset-file-map');
        if (is_file(__DIR__ . '/asset-file-map.php')) {
            unlink(__DIR__ . '/asset-file-map.php');
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
     * @param string $nameUsedByApp
     * @param string $sourcePath
     * @param string $targetPath
     *
     * @return string (hash-prefixed name)
     */
    private static function copyFile($nameUsedByApp, $sourcePath, $targetPath)
    {
        $filename = basename($nameUsedByApp);
        $prefixedName = sprintf(
            '%s-%s',
            md5_file($sourcePath . '/' . $filename),
            $filename
        );
        copy($sourcePath . '/' . $filename, $targetPath . '/' . $prefixedName);
        return $prefixedName;
    }
}
