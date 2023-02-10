<?php
    ini_set('display_errors', 0);

    $url = 'http://www.koeri.boun.edu.tr/scripts/lst5.asp';
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_PROXY, '');
    $data = curl_exec($ch);
    curl_close($ch);

    $dom = new DOMDocument();
    @$dom->loadHTML($data);

    $xpath = new DOMXPath($dom);
    


    if(isset($_GET["limit"]) && is_numeric($_GET["limit"])){
        $limit = $_GET["limit"];
    }else{
        $limit = 500;
    }
    if(isset($_GET["enlem"]) || isset($_GET["boylam"]) && is_float($_GET["boylam"]) && is_float($_GET["enlem"])){
        $filter = true;
    }else{
        $filter = false;
    }

    if(isset($_GET["arama"]) && !empty($_GET["arama"])){
        $search = true;
    }else{
        $search = false;
    }

    $nodes = $xpath->query('/html/body/pre');
    $keys = ["Tarih","Saat","Enlem(N)","Boylam(E)","Derinlik(km)","MD","ML","Mw"];
    $keys2 = ["Yer","Nitelik","RevizeTarih"];
    
    if(isset($nodes)){
        foreach( $nodes as $node )
        {
            $res = [];
            $value = trim($node->nodeValue);
            // $value = file_get_contents("data.txt");
            $array = array_slice(explode("\n",$value),6);
            for ($i=0; $i<count($array); $i++) {
                $filtered = createArray(" ",$array[$i],[0,70]);
                $dumpdata = createArray("   ",$array[$i],70);
                for($j=0; $j<count($filtered); $j++){
                    $res[$i][$keys[$j]] = hazirla("/[()]+/","",$filtered[$j]);
                }
                for($l=0; $l<count($dumpdata); $l++){
                    $res[$i][$keys2[$l]] = hazirla("/[()]+/","",$dumpdata[$l]); 
                }
            }
            if($filter){
                $enlem = $_GET["enlem"];
                $boylam = $_GET["boylam"];
                $res = array_filter($res, function($r) use ($enlem,$boylam) {
                    return $r["Enlem(N)"] == ($enlem ?? 0) || $r["Boylam(E)"] == ($boylam ?? 0);
                });
            }
            if($search){
                $searchString = $_GET["arama"];
                $res = array_filter($res, function ($v) use ($searchString) {
                    return is_numeric(strpos(mb_strtoupper($v["Yer"]),mb_strtoupper($searchString))) ? true : false;
                });
            }
            if($limit){
                $res = array_slice($res,0,$limit);
            }
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
        }
    }

    function createArray($delimiter,$arr,$slice){
        $str = is_array($slice) ? substr($arr,$slice[0],$slice[1]) : substr($arr,$slice);
        return array_values(array_filter(explode($delimiter, $str)));
    }
    function hazirla($regex,$rep,$data){
        return trim(preg_replace($regex,$rep,$data));
    }