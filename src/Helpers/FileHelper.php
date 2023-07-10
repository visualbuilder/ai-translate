<?php

namespace Visualbuilder\AiTranslate\Helpers;

class FileHelper
{
    
    public static function getLanguageFileList($sourceLocale)
    {
        $directories = config('ai-translate.source_directories');
        
        $filenamesArray = [];
        
        foreach ($directories as $directory) {
            $directory = $directory.'/'.$sourceLocale;
            if(file_exists($directory)) {
                $filenamesArray = array_merge($filenamesArray, self::getFiles($directory, $directory));
            }
        }
        
        return array_combine($filenamesArray, $filenamesArray);
    }
    
    /**
     * Recursively get all files in a directory and children
     */
    private static function getFiles($dir, $basepath)
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
                    $subFiles[] = $entryPath;
                }
            }
            closedir($handle);
            sort($subFiles);
            $files = array_merge($files, $subFiles);
            foreach ($subdirs as $subdir) {
                $files = array_merge($files, self::getFiles($subdir, $basepath));
            }
        }
        
        return $files;
    }
    
    public static function countItemsAndStringLengths($filename) {
        $translations = include($filename);
        
        return self::countItemsAndStringLengthsInArray($translations);
    }
    
    private static function countItemsAndStringLengthsInArray($translations) {
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
            'totalLength' => $totalLength
        ];
    }

}