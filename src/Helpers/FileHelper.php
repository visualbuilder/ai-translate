<?php

namespace Visualbuilder\AiTranslate\Helpers;

class FileHelper
{
    public static function getFileList($sourceLocale)
    {
        $jsonFiles = self::getJsonSourceFileList($sourceLocale);
        $phpFiles = self::getLanguageFileList($sourceLocale);

        return array_merge($jsonFiles, $phpFiles);
    }

    public static function getJsonSourceFileList($sourceLocale)
    {
        $directories = config('ai-translate.source_directories');
        $sourceFile = "$sourceLocale.json";
        $filenamesArray = [];

        foreach ($directories as $directory) {
            if(file_exists($directory)) {
                $filenamesArray = array_merge($filenamesArray, self::getFiles($directory, $directory, $sourceFile));
            }
        }

        return array_combine($filenamesArray, $filenamesArray);

    }

    public static function getLanguageFileList($sourceLocale)
    {
        $directories = config('ai-translate.source_directories');

        $filenamesArray = [];

        foreach ($directories as $directory) {
            $directory = $directory.'/'.$sourceLocale;
            if(file_exists($directory)) {
                $filenamesArray = array_merge($filenamesArray, self::getFiles($directory, $directory, 'php'));
            }
        }

        return array_combine($filenamesArray, $filenamesArray);
    }

    /**
     * Recursively get all files in a directory and children
     * Optionally provide a filetype such as php or a specific filename
     *
     * @param $dir
     * @param $basepath
     * @param $fileType
     *
     * @return array
     */
    private static function getFiles($dir, $basepath, $fileType = '*')
    {
        $files = $subdirs = $subFiles = [];

        if($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if($entry == "." || $entry == "..") {
                    continue;
                }
                if(substr($entry, 0, 1) == '_') {
                    continue;
                }
                $entryPath = $dir.'/'.$entry;
                if(is_dir($entryPath)) {
                    $subdirs[] = $entryPath;
                } else {
                    if ($fileType == '*') {
                        $subFiles[] = $entryPath;
                    } elseif (strpos($fileType, '.') === false) {
                        // fileType is assumed to be an extension
                        $fileExtension = pathinfo($entryPath, PATHINFO_EXTENSION);
                        if($fileExtension == $fileType) {
                            $subFiles[] = $entryPath;
                        }
                    } else {
                        // fileType is assumed to be a filename
                        $filename = pathinfo($entryPath, PATHINFO_BASENAME);
                        if ($filename == $fileType) {
                            $subFiles[] = $entryPath;
                        }
                    }
                }
            }
            closedir($handle);
            sort($subFiles);
            $files = array_merge($files, $subFiles);
            foreach ($subdirs as $subdir) {
                $files = array_merge($files, self::getFiles($subdir, $basepath, $fileType));
            }
        }

        return $files;
    }

    public static function getExtension($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    public static function countItemsAndStringLengths($filename)
    {

        switch (self::getExtension($filename)) {
            case('php'):
                $translations = include($filename);

                break;
            case('json'):
                $translations = json_decode(file_get_contents($filename), true);
        }


        return self::countItemsAndStringLengthsInArray($translations);
    }

    private static function countItemsAndStringLengthsInArray($translations)
    {
        // Initialize an array to hold the lengths and total length variable
        $lengths = [];
        $totalLength = 0;
        $itemCount = 0;

        // Iterate over the translations
        foreach ($translations as $key => $value) {
            if (is_array($value)) {
                $nestedResults = self::countItemsAndStringLengthsInArray($value);
                $itemCount += $nestedResults['itemCount'];
                $totalLength += $nestedResults['totalLength'];
                $lengths[$key] = $nestedResults['stringLengths'];
            } else {
                // Count the number of characters in the string
                $length = strlen($value);
                $lengths[$key] = $length;

                // Accumulate the total length
                $totalLength += $length;

                // Count item
                $itemCount++;
            }
        }

        // Return the number of items, the lengths array, and total length
        return [
            'itemCount' => $itemCount,
            'stringLengths' => $lengths,
            'totalLength' => $totalLength,
        ];
    }
}
