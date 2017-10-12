<?php
namespace Controller;

use Exception\ErrorException;
use Model\Request;
use Utils\HlsSreamer;


class DVRController {

    private $config = array();
    private $request;
    


    public function __construct(Request $request, array $config)
    {
        $this->config = $config;
        $this->request = $request;
    }

    public function timeShiftAction($channel_id, $time) {

        $session_key = "X-Playback-Session-Id";

        if(!isset(getallheaders()[$session_key])) throw new ErrorException("Not found header: X-Playback-Session-Id",500);

        if(isset(getallheaders()[$session_key])) session_id(getallheaders()[$session_key]);

        session_start(['gc_maxlifetime' => 60]);

        $hlsStreamer = new HlsSreamer($channel_id);

        $segments = $hlsStreamer->getSegmentsByTime($time);

        if(!array_key_exists("time",$_SESSION)) {
            $_SESSION["time"] = time();
            $_SESSION["segment"] =  end($segments);
            $count = 0;
        } else {
            $old_time = (int)$_SESSION["time"];
            $current_time = time();
            $time += ($current_time - $old_time);
            $segmentsr = $hlsStreamer->getSegmentsByTime($time);
            $first_segment = $_SESSION["segment"];
            $current_segmant = end($segmentsr);
            $count = ($current_segmant->getEndTime() - $first_segment->getEndTime()) / 10;
        }
        reset($segments);

        $host = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];

        $archive_path = "http://$host:$port/archive/$channel_id/";

        return $this->render('hls_playlist_m3u8.php', [
            'segments' => $hlsStreamer->getSegmentsByTime($time),
            'path' => $archive_path,
            'count' => $count
        ]);
    }

    private function render($file, $variables = array())
    {
        extract($variables);

        ob_start();
        include '../View/' . $file;
        $renderedView = ob_get_clean();

        return $renderedView;
    }
}