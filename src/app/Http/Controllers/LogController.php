<?php

namespace Backpack\LogManager\app\Http\Controllers;

use Backpack\LogManager\app\Classes\LogViewer;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class LogController extends Controller
{
    private static $levels_classes = [
        'debug'     => 'info',
        'info'      => 'info',
        'notice'    => 'info',
        'warning'   => 'warning',
        'error'     => 'danger',
        'critical'  => 'danger',
        'alert'     => 'danger',
        'emergency' => 'danger',
        'processed' => 'info',
    ];

    /**
     * Map debug levels to icon classes.
     *
     * @var array
     */
    private static $levels_imgs = [
        'debug'     => 'info',
        'info'      => 'info',
        'notice'    => 'info',
        'warning'   => 'warning',
        'error'     => 'warning',
        'critical'  => 'warning',
        'alert'     => 'warning',
        'emergency' => 'warning',
        'processed' => 'info',
    ];

    /**
     * Log levels that are used.
     *
     * @var array
     */
    private static $log_levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
        'processed',
    ];
    /**
     * Lists all log files.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $this->data['files'] = $this->getFiles();
        $this->data['title'] = trans('romansev::logmanager.log_manager');

        return view('logmanager::logs', $this->data);
    }

    /**
     * Previews a log file.
     *
     * @throws \Exception
     */
    public function preview($file_name)
    {
        $logsPath = storage_path('logs');
        $file = $logsPath.'/'.decrypt($file_name);

        $logs = $this->getLog($file);

        if (count($logs) <= 0) {
            abort(404, trans('romansev::logmanager.log_file_doesnt_exist'));
        }

        $this->data['logs'] = $logs;
        $this->data['title'] = trans('romansev::logmanager.preview').' '.trans('romansev::logmanager.logs');
        $this->data['file_name'] = decrypt($file_name);

        return view('logmanager::log_item', $this->data);
    }

    /**
     * Downloads a log file.
     *
     * @param $file_name
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($file_name)
    {
        return response()->download(LogViewer::pathToLogFile(decrypt($file_name)));
    }

    /**
     * Deletes a log file.
     *
     * @param $file_name
     *
     * @throws \Exception
     *
     * @return string
     */
    public function delete($file_name)
    {
        if (config('backpack.logmanager.allow_delete') == false) {
            abort(403);
        }

        if (app('files')->delete(LogViewer::pathToLogFile(decrypt($file_name)))) {
            return 'success';
        }

        abort(404, trans('romansev::logmanager.log_file_doesnt_exist'));
    }

    private function getLog($file)
    {
        $log = [];


        $file = app('files')->get($file);

        $pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.\d{6}\].*/';

        preg_match_all($pattern, $file, $headings);

        if (!is_array($headings)) {
            return $log;
        }

        $stack_trace = preg_split($pattern, $file);

        if ($stack_trace[0] < 1) {
            array_shift($stack_trace);
        }

        foreach ($headings as $h) {
            $h = array_slice($h, -10);
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach (static::$log_levels as $level) {
                    if (strpos(strtolower($h[$i]), '.'.$level) || strpos(strtolower($h[$i]), $level.':')) {
                        $pattern = '/^\[(?P<date>(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.\d{6}))\](?:.*?(?P<context>(\w+))\.|.*?)'.$level.': (?P<text>.*?)(?P<in_file> in .*?:[0-9]+)?$/i';
                        preg_match($pattern, $h[$i], $current);
                        if (!isset($current['text'])) {
                            continue;
                        }

                        $jsonStart = strpos($current['text'], '{');

                        $text = substr($current['text'], $jsonStart);

                        $log[] = [
                            'context'     => $current['context'],
                            'level'       => $level,
                            'level_class' => static::$levels_classes[$level],
                            'level_img'   => static::$levels_imgs[$level],
                            'date'        => $current['date'],
                            'text'        => $text,
                            'in_file'     => isset($current['in_file']) ? $current['in_file'] : null,
                            'stack'       => ''
                        ];
                    }
                }
            }
        }

        return array_reverse($log);
    }

    private function getFiles()
    {
        $filesAnswers = glob(storage_path().'/logs/*ANSWERS*.log');
        $filesAnswers = array_slice($filesAnswers, -3);
        $filesErrors = glob(storage_path().'/logs/*TRAVEL_API*.log');
        $filesErrors = array_slice($filesErrors, -3);
        $files = array_merge($filesAnswers, $filesErrors);

        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');

        if (is_array($files)) {
            foreach ($files as $k => $file) {
                $disk = Storage::disk(config('backpack.base.root_disk_name'));
                $file_name = basename($file);

                if ($disk->exists('storage/logs/'.$file_name)) {
                    $files[$k] = [
                        'file_name'     => $file_name,
                        'file_size'     => $disk->size('storage/logs/'.$file_name),
                        'last_modified' => $disk->lastModified('storage/logs/'.$file_name),
                    ];
                }
            }
        }

        return array_values($files);

    }
}
