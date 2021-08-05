<?php
include_once("koneksi.php");
session_start();

function get($param, $secure = true){
    global $koneksi;
    $d = isset($_GET[$param]) ? $_GET[$param] : null;
    if($secure){
        $d = mysqli_real_escape_string($koneksi, $d);
        $d = filter_var($d, FILTER_SANITIZE_STRING);
    }
    
    return $d;
}

function post($param, $secure = true){
    global $koneksi;
    $d = isset($_POST[$param]) ? $_POST[$param] : null;
    if($secure){
        $d = mysqli_real_escape_string($koneksi, $d);
        $d = filter_var($d, FILTER_SANITIZE_STRING);
    }
    return $d;
}

function login($u, $p){
    global $koneksi;
    $p = sha1($p);
    $q = mysqli_query($koneksi, "SELECT * FROM account WHERE username='$u' AND password='$p'");

    if(mysqli_num_rows($q)){
        $_SESSION['login'] = true;
        $_SESSION['username'] = $u;
        $_SESSION['level'] = getSingleValDB("account", "username", $u, "level");
        return true;
    }else{
        return false;
    }
}

function cekSession(){
    $login = isset($_SESSION['login']) ? $_SESSION['login'] : null;
    if($login == true){
        return 1;
    }else{
        return 0;
    }
}

function getSingleValDB($table, $where, $param, $target){
    global $koneksi;
    $q = mysqli_query($koneksi, "SELECT * FROM `$table` WHERE `$where`='$param'");
    $row = mysqli_fetch_assoc($q);
    return $row[$target];
}

function countDB($table, $where = null, $param = null){
    global $koneksi;
    if($where == null && $param == null){
        $q = mysqli_query($koneksi, "SELECT * FROM `$table`");
    }else{
        $q = mysqli_query($koneksi, "SELECT * FROM `$table` WHERE `$where`='$param'");
    }
    $row = mysqli_num_rows($q);
    return $row;
}

function countPresentase(){
    $a = countDB("pesan", "status", "TERKIRIM");
    $b = countDB("pesan");
    if($a > 0){
        return (countDB("pesan", "status", "TERKIRIM")/countDB("pesan"))*100;
    }else{
        return 0;
    }
}

function getAllNumber(){
    global $koneksi;
    $q = mysqli_query($koneksi, "SELECT * FROM `nomor`");
    $arr = [];
    while($row = mysqli_fetch_assoc($q)){
        array_push($arr, $row['nomor']);
    }
    return $arr;
}

function getLastID($table){
    global $koneksi;
    $q = mysqli_query($koneksi, "SELECT * FROM `$table` ORDER BY id DESC LIMIT 1");
    $row = mysqli_fetch_assoc($q);
    return $row['id'];
}

function url_wa(){
    return getSingleValDB("pengaturan", "id", "1", "wa_gateway_url");
}

function api_key(){
    return getSingleValDB("pengaturan", "id", "1", "api_key");
}

function redirect($target){
    echo '
    <script>
    window.location = "'.$target.'";
    </script>
    ';
    exit;
}

function toastr_set($status, $msg){
    $_SESSION['toastr'] = true;
    $_SESSION['toastr_status'] = $status;
    $_SESSION['toastr_msg'] = $msg;
}

function toastr_show(){
    $t = isset($_SESSION['toastr']) ? $_SESSION['toastr'] : null;
    $t_s = isset($_SESSION['toastr_status']) ? $_SESSION['toastr_status'] : null;
    $t_m = isset($_SESSION['toastr_msg']) ? $_SESSION['toastr_msg'] : null;
    if($t == true){
        if($t_s == "success"){
            echo "
            toastr.success('Sukses', '".$t_m."');
            ";
        }

        if($t_s == "error"){
            echo "
            toastr.error('Kesalahan', '".$t_m."');
            ";
        }

        unset($_SESSION['toastr']);
        unset($_SESSION['toastr_status']);
        unset($_SESSION['toastr_msg']);
    }
}

function sendMSG($number, $msg){
    $url = url_wa()."/send";
    $data = [
        "number" => $number,
        "message" => $msg
    ];
    $data = json_encode($data);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $response = curl_exec($curl);
    return json_decode($response, true);
}

function sendIMG($number, $msg, $file){
    $url = url_wa()."/send-media";
    $data = [
        "number" => $number,
        "caption" => $msg,
        "file" => $file
    ];
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);
    return json_decode($response, true);
}

function cekStatusWA(){
    $url = url_wa()."/status";

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    //curl_setopt($curl, CURLOPT_POST, 1);
    //curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $response = curl_exec($curl);
    return json_decode($response, true);
}

function updateStatusMSG($id, $a){
    global $koneksi;
    $q = mysqli_query($koneksi, "UPDATE pesan SET status='$a' WHERE id='$id'");
}

function base64upload($base64_string, $output_file) {
    $file = fopen($output_file, "wb");

    $data = explode(',', $base64_string);

    fwrite($file, base64_decode($data[1]));
    fclose($file);

    return $output_file;
}

function phoneToStandard($nomor){
    $nomor = explode("@", $nomor)[0];
    $nomor = substr($nomor, 2);
    $nomor = "0".$nomor;

    return $nomor;
}

function sendApiUrl(){
    global $base_url;
    return $base_url."api/send.php?key=".getSingleValDB("pengaturan", "id", "1", "api_key");
}

function syncMSG(){
    global $koneksi;
    $url = url_wa()."/getChat";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);
    $res = json_decode($response, true);
    $final = [];
    $arr_p = [];
    foreach($res['response'] as $sender){
        foreach($sender as $s){
            $id_pesan = $s['id']['id'];
            if(checkExist("receive_chat", "id_pesan", $id_pesan) == false){
                if($s["fromMe"] == true){
                    $nomor = phoneToStandard($s['to']);
                    $fromme = "1";
                }else{
                    $nomor = phoneToStandard($s['from']);
                    $fromme = "0";
                }
                if(in_array($nomor, $arr_p)){
    
                }else{
                    $final[$nomor] = [];
                    array_push($arr_p, $nomor);
                }
                $pesan = $s['body'];
                $tanggal = date("Y-m-d H:i:s", $s['timestamp']);
                $ret = [
                    'id' => $id_pesan,
                    'nomor' => $nomor,
                    'pesan' => $pesan,
                    'fromMe' => $fromme,
                    'tanggal' => $tanggal
                ];
    
                $nomor_saya = getSingleValDB("pengaturan", "id", "1", "nomor");
    
                $q = mysqli_query($koneksi, "INSERT INTO receive_chat(`id_pesan`, `nomor`, `pesan`, `tanggal`, `from_me`, `nomor_saya`)
                                    VALUE('$id_pesan', '$nomor', '$pesan', '$tanggal', '$fromme', '$nomor_saya')");
    
                array_push($final[$nomor], $ret);
            }    
        }
    }
}

function getContact(){
    global $koneksi;
  
    $nomor_saya = getSingleValDB("pengaturan", "id", "1", "nomor");
    $q = mysqli_query($koneksi, "SELECT DISTINCT nomor, MAX(tanggal) FROM receive_chat WHERE nomor_saya='$nomor_saya' GROUP BY nomor ORDER BY MAX(tanggal) DESC");
    return $q;
}
  
function getLastMsg($nomor){
    global $koneksi;
  
    $nomor_saya = getSingleValDB("pengaturan", "id", "1", "nomor");
    $q = mysqli_query($koneksi, "SELECT * FROM receive_chat WHERE nomor='$nomor' AND nomor_saya='$nomor_saya' ORDER BY tanggal DESC LIMIT 1");
    $row = mysqli_fetch_assoc($q);
    if(date("Y-m-d", strtotime($row['tanggal'])) == date("Y-m-d")){
      $row['tanggal'] = date("H:i", strtotime($row['tanggal']));
    }else{
      $row['tanggal'] = date("d M y H:i", strtotime($row['tanggal']));
    }
    return $row;
}

function checkExist($table, $where, $param){
    global $koneksi;
    $q = mysqli_query($koneksi, "SELECT * FROM `$table` WHERE `$where`='$param' LIMIT 1");
    $row = mysqli_num_rows($q);
    if($row == 0){
        return false;
    }else{
        return true;
    }
}

function callback($id_pesan, $nomor, $pesan, $tanggal, $nomor_saya){
    $url = getSingleValDB("pengaturan", "id", "1", "callback");

    if($url != null){
        $data = [
            "id_pesan" => $id_pesan,
            "nomor" => $nomor,
            "pesan" => $pesan,
            "tanggal" => $tanggal,
            "nomor_saya" => $nomor_saya
        ];
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        return json_decode($response, true);
    }
}

function cekNomorWhatsapp($number){
    $url = url_wa()."/cek-nomor";
    $data = [
        "number" => $number
    ];
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);
    $r = json_decode($response, true);
    return $r['status'];
}

function autoReply($nomor, $pesan){
    global $koneksi;
    $q = mysqli_query($koneksi, "SELECT * FROM autoreply");
    while($row = mysqli_fetch_assoc($q)){
        if($row['case_sensitive'] == "0"){
            //NON SENSITIF
            $pesan = strtolower($pesan);
            if(stripos($pesan, $row['keyword']) !== false){
                sendMSG($nomor, $row['response']);
                return true;
                break;
            }
        }else{
            //SENSITIF
            if($pesan == $row['keyword']){
                sendMSG($nomor, $row['response']);
                return true;
                break;
            }
        }
    }
}

function serializeToJson($ser){
    $arr = unserialize($ser);
    return json_encode($arr);
}

function groupsIdToNumber($id){
    global $koneksi;
    $final = [];
    foreach($id as $i){
        $q = mysqli_query($koneksi, "SELECT * FROM groups WHERE id='$i'");
        $row = mysqli_fetch_assoc($q);
        $phone = unserialize($row['anggota']);
        foreach($phone as $p){
            if(!in_array($p, $final)){
                array_push($final, $p);
            }
        }
    }
    
    return $final;
}

function kirimGroup(){
    global $koneksi;
    $count = 0;
    date_default_timezone_set('Asia/Jakarta');
    $now = strtotime(date("Y-m-d H:i:s"));
    $chunk = getSingleValDB("pengaturan", 'id', '1', 'chunk');
    $q = mysqli_query($koneksi, "SELECT * FROM blast WHERE UNIX_TIMESTAMP(jadwal) <= '$now' AND status='MENUNGGU JADWAL' ORDER BY id ASC LIMIT $chunk");
    while($row = mysqli_fetch_assoc($q)){
        if($row['media'] == null){
            $n = unserialize($row['nomor']);
            foreach($n as $nn){
                $send = sendGroup($nn, $row['pesan']);
            }
        }else{
            //$send = sendIMG($row['nomor'], $row['pesan'], $row['media']);
            //if($send['status'] == "true"){
            //    updateStatusMSG($row['id'], "TERKIRIM");
            //}else{
            //    updateStatusMSG($row['id'], "GAGAL");
            //}
        }
        updateStatusGroupBlast($row['id'], "TERKIRIM");
        $count++;
    }
}

function sendGroup($group, $pesan){
    $url = url_wa()."/send-group";
    $data = [
        "name" => $group,
        "message" => $pesan
    ];
    $data = json_encode($data);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $response = curl_exec($curl);
    return json_decode($response, true);
}

function updateStatusGroupBlast($id, $a){
    global $koneksi;
    $q = mysqli_query($koneksi, "UPDATE blast SET status='$a' WHERE id='$id'");
}