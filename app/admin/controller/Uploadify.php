<?php

namespace app\admin\controller;

use think\facade\View;

class Uploadify extends Base
{

    public function upload()
    {
        $func = input('func');
        $path = input('path', 'temp');
        $image_upload_limit_size = config('app.image_upload_limit_size');
        $fileType = input('fileType', 'Images');  //上传文件类型，视频，图片
        if ($fileType == 'Flash') {
            $upload = url('Ueditor/videoUp', array('savepath' => $path, 'pictitle' => 'banner', 'dir' => 'video'))->build();
            $type = 'mp4,3gp,flv,avi,wmv';
        }else if ($fileType=='imageVideo'){
            $upload = url('Ueditor/upFile', ['savepath' => $path, 'pictitle' => 'banner', 'dir' => 'image_video'])->build();
            $type = 'mp4,3gp,jpg,png,gif,jpeg';
        } else {
            $upload = url('Ueditor/upFile', array('savepath' => $path, 'pictitle' => 'banner', 'dir' => 'images'))->build();
            $type = 'jpg,jpeg,gif,png,pdf,doc,docx,ppt,pptx,xls,xlsx';
        }
        $num=input('num/d');
        $info=[
            'fileType' => $fileType,
            'title' => '',
            'upload' => $upload,
            'fileList' => url('Uploadify/fileList', array('path' => $path))->build(),
            'size' => $image_upload_limit_size / (1024 * 1024) . 'M',
            'type' => $type,
            'input' => input('input'),
            'func' => empty($func) ? 'undefined' : $func,
            'num' => $num
        ];
        View::assign('info', $info);
        return view();
    }
    /**
     * 删除上传的图片,视频
     */
    public function delupload()
    {
        $action = input('action', 'del');
        $filename = input('filename');
        $filename = empty($filename) ? input('url') : $filename;
        $filename = str_replace('../', '', $filename);
        $filename = trim($filename, '.');
        $filename = trim($filename, '/');
        if ($action == 'del' && !empty($filename) && file_exists($filename)) {
            $filetype = strtolower(strstr($filename, '.'));
            $phpfile = strtolower(strstr($filename, '.php'));  //排除PHP文件
            $erasable_type = config('app.erasable_type');  //可删除文件
            if (!in_array($filetype, $erasable_type) || $phpfile) {
                exit(0);
            }
            if (unlink("./".$filename)) {
//                $this->deleteWechatImage(input('url'));
                echo 1;
            } else {
                echo 0;
            }
        }
    }

    public function fileList()
    {
        /* 判断类型 */
        $type = input('type', 'Images');
        switch ($type) {
            /* 列出图片 */
            case 'Images' :
                $allowFiles = 'png|jpg|jpeg|gif|bmp';
                break;

            case 'Flash' :
                $allowFiles = 'mp4|3gp|flv|avi|wmv|flash|swf';
                break;

            /* 列出文件 */
            default :
                $allowFiles = '.+';
        }
        $path = UPLOAD_PATH . input('path', 'temp');
        //echo file_exists($path);echo $path;echo '--';echo $allowFiles;echo '--';echo $key;exit;
        $listSize = 100000;

        $key = empty($_GET['key']) ? '' : $_GET['key'];

        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = $start + $size;

        /* 获取文件列表 */
        $files = $this->getfiles($path, $allowFiles, $key, ['./upload/goods/thumb']);

        if (!count($files)) {
            echo json_encode(array(
                "state" => "没有相关文件",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ));
            exit;
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--) {
            $list[] = $files[$i];
        }

        /* 返回数据 */
        $result = json_encode(array(
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ));
        echo $result;
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param array $files
     * @return array
     */
    private function getfiles($path, $allowFiles, $key, $ignore = array(), &$files = array())
    {
        $dir=".".$path;
        if (!is_dir($dir)) return null;
        if (substr($dir, strlen($dir) - 1) != '/') $dir .= '/';
        $handle = opendir($dir);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $dir . $file;
                $path3 = $path ."/". $file;
                if (is_dir($path2) && !in_array($path2, $ignore)) {
                    $this->getfiles($path3, $allowFiles, $key, array(), $files);
                } else {
                    if (preg_match("/\.(" . $allowFiles . ")$/i", $file) && preg_match("/.*" . $key . ".*/i", $file)) {
                        $files[] = array(
                            'url' =>  $path3,
                            'name' => $file,
                            'mtime' => filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }

    public function preview()
    {

        // 此页面用来协助 IE6/7 预览图片，因为 IE 6/7 不支持 base64
        $DIR = 'preview';
        // Create target dir
        if (!file_exists($DIR)) {
            @mkdir($DIR);
        }

        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds

        if ($cleanupTargetDir) {
            if (!is_dir($DIR) || !$dir = opendir($DIR)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }

            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $DIR . DIRECTORY_SEPARATOR . $file;
                // Remove temp file if it is older than the max age and is not the current file
                if (@filemtime($tmpfilePath) < time() - $maxFileAge) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }

        $src = file_get_contents('php://input');
        if (preg_match("#^data:image/(\w+);base64,(.*)$#", $src, $matches)) {
            $previewUrl = sprintf(
                "%s://%s%s",
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']
            );
            $previewUrl = str_replace("preview.php", "", $previewUrl);
            $base64 = $matches[2];
            $type = $matches[1];
            if ($type === 'jpeg') {
                $type = 'jpg';
            }

            $filename = md5($base64) . ".$type";
            $filePath = $DIR . DIRECTORY_SEPARATOR . $filename;

            if (file_exists($filePath)) {
                die('{"jsonrpc" : "2.0", "result" : "' . $previewUrl . 'preview/' . $filename . '", "id" : "id"}');
            } else {
                $data = base64_decode($base64);
                $filePathLower = strtolower($filePath);
                if (strstr($filePathLower, '../') || strstr($filePathLower, '..\\') || strstr($filePathLower, '.php')) {
                    die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "文件上传格式错误 error ！"}}');
                }
                file_put_contents($filePath, $data);
                die('{"jsonrpc" : "2.0", "result" : "' . $previewUrl . 'preview/' . $filename . '", "id" : "id"}');
            }
        } else {
            die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "un recoginized source"}}');
        }
    }

    public function wechatImageList($listSize, $get)
    {
        $size = isset($get['size']) ? intval($get['size']) : $listSize;
        $start = isset($get['start']) ? intval($get['start']) : 0;

        $logic = new \app\common\logic\WechatLogic;
        return $logic->getPluginImages($size, $start);
    }

    public function deleteWechatImage($file_path)
    {
        $logic = new \app\common\logic\WechatLogic;
        $logic->deleteImage($file_path);
    }
}