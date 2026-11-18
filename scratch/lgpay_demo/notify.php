<?php

function md5_sign($data,$key,$unset=[]){

    // $data=array_filter($data);
    ksort($data);
    foreach ($unset as $value){
        unset($data[$value]);
    }

    $string = http_build_query($data);
    $string = urldecode($string); //签名的时候不需要做encode

    $string=trim($string) . "&key=" . $key;

    // dd($string); 
    return strtoupper(md5($string));

}

$key = "...";

$sign = md5_sign($_POST,$key,['sign']);
if($sign == $_POST['sign']){
    // ====== DO YOUR BESSINESS HERE ======
    // ====== DO YOUR BESSINESS HERE ======
    // ====== DO YOUR BESSINESS HERE ======
}

echo "success";