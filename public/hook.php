<?php

$requestBody = file_get_contents("php://input");
if (empty($requestBody)) {
    die('send fail');
}
/**
 * ---------------git使忽略文件生效------------
 * git rm -r --cached .
 * git add .
 * ---------------git强制拉取------------
 * git fetch --all
 * git reset --hard origin/master
 * git pull //可以省略
 */
$content = json_decode($requestBody, true);
$path='/www/wwwroot/liyilian';
$pass='e2e968cd0f40bfa8e8457ae635c4075e';
if ($content['ref'] == 'refs/heads/master' && $content['password'] == $pass) {
    exec("cd $path && git pull 2<&1", $output, $return);
    $res_log = PHP_EOL . '----------------------------------------------------------------------------------------------------' . PHP_EOL;
    $res_log .= $content['user_name'] . ' 在' . date('Y-m-d H:i:s') . '向' . $content['repository']['name'] . '项目的' . $content['ref'] . '分支push了' . $content['total_commits_count'] . '个commit：';
    $res_log .= PHP_EOL . "pull start --------" . PHP_EOL;
    $res_log .= '$output:' . var_export($output, true) . PHP_EOL . '$return:' . var_export($return, true) . PHP_EOL;
    $res_log .= PHP_EOL . "pull end --------" . PHP_EOL;
    //$res_log .= var_export($requestBody, true);
    file_put_contents('git_webhook.log', $res_log, FILE_APPEND);//写入日志到log文件中
}