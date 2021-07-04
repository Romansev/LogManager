<?php

namespace Backpack\LogManager\app\Classes;

use Illuminate\Support\Facades\Storage;

/**
 * Class LogViewer.
 */
class LogViewer
{
    /**
     * @param string $file
     *
     * @throws
     *
     * @return string
     */
    public static function pathToLogFile($file)
    {
        $logsPath = storage_path('logs');

        if (app('files')->exists($file)) { // try the absolute path
            return $file;
        }

        $file = $logsPath.'/'.$file;

        // check if requested file is really in the logs directory
        if (dirname($file) !== $logsPath) {
            throw new \Exception('No such log file');
        }

        return $file;
    }

}
