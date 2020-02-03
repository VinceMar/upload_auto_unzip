<?php

$fileName = $_FILES['stuFile']['name'];
$fileType = substr(strrchr($fileName, '.'), '1');
$newFileName = time() . '_' . generateRandomString(3) . '.' . $fileType;

$uploadFileSavePath = './upload/';
$unZipFileSavePath = './unzip/' . $newFileName . '/';

if (move_uploaded_file($_FILES['stuFile']['tmp_name'], $uploadFileSavePath . $newFileName)) {
    get_zip_originalsize($uploadFileSavePath . $newFileName, $unZipFileSavePath);
    $dir = str_replace("\\", "/", __DIR__) . '/unzip/' . $newFileName;

    //查找出默认首页的路径
    $defaultIndex = '';
    $find = findIndex($dir, $defaultIndex);

    $finalUlr = rela_pos(str_replace("\\", "/", __DIR__), $defaultIndex);

    echo $finalUlr;
} else {
    echo "error";
}

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function get_zip_originalsize($filename, $path)
{
    //先判断待解压的文件是否存在
    if (!file_exists($filename)) {
        die("文件 $filename 不存在！");
    }

    //将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
    $filename = iconv("utf-8", "gb2312", $filename);
    $path = iconv("utf-8", "gb2312", $path);
    //打开压缩包
    $resource = zip_open($filename);
    $i = 1;
    //遍历读取压缩包里面的一个个文件
    while ($dir_resource = zip_read($resource)) {
        //如果能打开则继续
        if (zip_entry_open($resource, $dir_resource)) {
            //获取当前项目的名称,即压缩包里面当前对应的文件名
            $file_name = $path . zip_entry_name($dir_resource);
            //以最后一个“/”分割,再用字符串截取出路径部分
            $file_path = substr($file_name, 0, strrpos($file_name, "/"));
            //如果路径不存在，则创建一个目录，true表示可以创建多级目录
            if (!is_dir($file_path)) {
                mkdir($file_path, 0777, true);
            }
            //如果不是目录，则写入文件
            if (!is_dir($file_name)) {
                //读取这个文件
                $file_size = zip_entry_filesize($dir_resource);
                //最大读取6M，如果文件过大，跳过解压，继续下一个
                if ($file_size < (1024 * 1024 * 30)) {
                    $file_content = zip_entry_read($dir_resource, $file_size);
                    file_put_contents($file_name, $file_content);
                } else {
                    echo "<p> " . $i++ . " 此文件已被跳过，原因：文件过大， -> " . iconv("gb2312", "utf-8", $file_name) . " </p>";
                }
            }
            //关闭当前
            zip_entry_close($dir_resource);
        }
    }
    //关闭压缩包
    zip_close($resource);
}

//查找index.html 的位置
function findIndex($dir, &$path)
{
    //获得根目录句柄
    $root = opendir($dir);
    //获取目录中下一个文件的文件名，成功返回文件名，失败返回false
    $filename = readdir($root);
    //排除目录"."和".."
    while ($filename == "." | $filename == "..") {
        // echo "debug---" . $filename . '<br>';
        $filename = readdir($root);
    }

    while ($filename) {

        //先全部转成小写 然后去空格
        $filename = trim(strtolower($filename));
        $rex = trim(strtolower('index.html'));

        //debug
        // echo "$filename --- $rex " . strcmp($filename, $rex) . " <br>";

        // echo "debug---" . $filename . '<br>';

        // 如果当前文件是文件夹,就递归调用
        if (is_dir($dir . "/" . $filename)) {
            findIndex($dir . "/" . $filename, $path);
        }

        if (strcmp($filename, $rex) == 0) {
//            echo "找到了$dir\\$filename<br>";
            $path = $dir . "/" . $filename;
            return true;
        }

        $filename = readdir($root);
    }
}

/**
 * 绝对路径转成相对 路径
 * $path相对于$base的相对路径
 * @param string $base
 * @param string $path
 * 思路：去除共同部分
 * @return string
 */

function rela_pos($base, $path)
{
    $base = explode('/', trim($base, '/'));
    $path = explode('/', trim($path, '/'));
    $ln1 = count($base);
    $ln2 = count($path);
    if ($ln1 > $ln2) {
        $i = 0;
        foreach ($path as $k => $v) {
            if ($v == $base[$k]) {
                $i++;
            } else {
                break;
            }
        }
    } else {
        $i = 0;
        foreach ($base as $k1 => $v1) {
            if ($v1 == $path[$k1]) {
                $i++;
            } else {
                break;
            }
        }
    }
    array_splice($base, 0, $i);
    array_splice($path, 0, $i);
    //当前两个路径有相同的根目录
    $b_len = count($base) - 1;
    $st = '';
    for ($j = 0; $j < $b_len; $j++) {
        $st .= '../';
    }
    return $st . implode('/', $path);
}