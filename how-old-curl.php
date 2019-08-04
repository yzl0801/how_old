<?php
//1.HOW_OLD_PHOTO_DIR放在web目录
//2.新建两个文件夹（1）how-old-source，放原始图片；（2）how-old-process，空文件夹，用来放处理后的图片
//3.本地浏览器输入localhost/how-old-curl.php
define("HOW_OLD_PHOTO_DIR", __DIR__);
defined("HOW_OLD_URL") or define("HOW_OLD_URL", "http://www.how-old.net/Home/Analyze");
//var_dump( memory_get_usage());  
$raw_file_list = scandir(HOW_OLD_PHOTO_DIR . '/how-old-source');

$arr_filename_list = [];
for ($idx = 2; $idx < count($raw_file_list); $idx++) {
    $arr_filename_list[] = HOW_OLD_PHOTO_DIR . '/how-old-source/' . $raw_file_list[$idx];
}
echo "fetch picture data success！<br><br>";
//print_r($arr_filename_list);

foreach ($arr_filename_list as $i => $f) {

    $file_info = pathinfo($f);
    $file_dirname = $file_info['dirname'];
    $file_basename = $file_info['basename'];
    $file_extension = $file_info['extension'];
    $file_filename = $file_info['filename'];

    echo "<hr>{$file_basename}处理结果：<br>";

    //初始化连接
    $ch = curl_init();

    //设置参数

    $postfields = [
        //'file' => "@{$f}",
        'file' => new CURLFile($f),
    ];

    curl_setopt($ch, CURLOPT_URL, HOW_OLD_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false); // required as of PHP 5.6.0
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

    //执行curl请求
    $result = curl_exec($ch);

    //错误处理
    if (false === $result) {
        echo 'CURL ERROR: ' . curl_error($ch);
    } else {
        echo "Request Success!<br>  ";
    }
    curl_close($ch);

    //两层转义，PHP第一层转义\\成\，在正则表达式中就是\\\\r\\\\n，
    //正则表达式第二层转义，最终匹配就是\\r\\n
    //第一次处理\\r\\n
    $str = preg_replace("/\\\\\\\\r\\\\\\\\n/", '', trim($result, '"'));
    //第二次处理\\"(\[.*\])\\"
    $str = preg_replace("/\\\\\"(\[.*\])\\\\\"/", '\1', $str);
    //第三次处理\
    $str = preg_replace("/\\\\/", '', $str);
    //对json格式字符串解析成JSON对象
    $obj = json_decode($str);
    //var_dump($obj);
    //释放资源

    $arr_img = [];

    $faces = $obj->Faces;
    echo "共" . count($faces) . "人<br>";
    foreach ($faces as $idx => $f_obj) {
        $arr_img[$idx]['sex'] = ('Female' == $f_obj->attributes->gender) ? 'F' : 'M';
        $arr_img[$idx]['age'] = $f_obj->attributes->age;
        echo "第" . ($idx + 1) . "人，" . "性别：{$arr_img[$idx]['sex']}，年龄：{$arr_img[$idx]['age']}<br>";
    }


    if (copy("{$file_dirname}/{$file_basename}",
        dirname($file_dirname) . "/how-old-process/{$file_basename}")) {
        $suffix = '';
        foreach ($arr_img as $idx => $face_info) {
            $suffix .= "_{$arr_img[$idx]['sex']}{$arr_img[$idx]['age']}";
        }
        //echo $suffix;
        if (rename(dirname($file_dirname) . "/how-old-process/{$file_basename}",
            dirname($file_dirname) . "\how-old-process\\{$file_filename}{$suffix}.{$file_extension}")
        ) {
            echo "Rename Succeess! <br> ";
        } else {
            echo "Rename file Error!<br>  ";
        }
    } else {
        echo "Copy file Error!<br>  ";
    }

    echo "源文件路径：" . "{$file_dirname}/{$file_basename}<br>";
    echo "处理文件路径：" . dirname($file_dirname) . "\how-old-process\\{$file_filename}{$suffix}.{$file_extension}";

    unset($obj);
}

//var_dump( memory_get_usage());  
  
  
  
