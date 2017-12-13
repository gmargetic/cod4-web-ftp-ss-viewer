<?php
// Copyright Goran Margetic - https://goran-margetic.iz.hr
// this is 5 minute work so yeah, it can be better

$cfg['ftp']['host'] = "";        // ip address or hostname
$cfg['ftp']['port'] = 21;        // ftp port - have to be FTP, not sftp/ssh
$cfg['ftp']['user'] = "";        // ftp username
$cfg['ftp']['pass'] = "";        // ftp password
$cfg['ftp']['timeout'] = 30;     // timeout to stop trying to connect after XX seconds - php's default is 90 seconds
$cfg['img']['dir'] = "/cod4_51/screenshots";  // without trailing slash

//-------------------------------------------------------------------------------------
function ftp_get_filelist($con, $path){
    $files = array();
    $contents = ftp_rawlist ($con, $path);
    $a = 0;
    if(count($contents)){
        foreach($contents as $line){
            preg_match("#([d?r?w?x?\-]+)([\s]+)([0-9]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z0-9_\.]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z]+)([\s ]+)([0-9]+)([\s]+)([0-9]+):([0-9]+)([\s]+)(.*)#si", $line, $out);
            if(@$out[3] != 1 && (@$out[18] == "." || @$out[18] == "..")){
                // do nothing for now
            } else {
                $a++;
                @$files[$a]['rights']    = $out[1];
                @$files[$a]['type']      = $out[3] == 1 ? "file":"folder";
                @$files[$a]['owner_id']  = $out[5];
                @$files[$a]['owner']     = $out[7];
                @$files[$a]['date_modified'] = $out[11]." ".$out[13] . " ".$out[13].":".$out[16]."";
                @$files[$a]['name']      = $out[18];
            }
        }
    }
    return $files;
}

function getPlayerName($val){
  $newPlayerName = pathinfo($val, PATHINFO_FILENAME);
  $newPlayerName = substr($newPlayerName, 0, -4);
  return $newPlayerName;
}

$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

$ftp_conn = ftp_connect($cfg['ftp']['host'], $cfg['ftp']['port'], $cfg['ftp']['timeout']) or die("Could not connect to ".$cfg['ftp']['host'].':'.$cfg['ftp']['port']);
$login    = ftp_login($ftp_conn, $cfg['ftp']['user'], $cfg['ftp']['pass']);

if(!empty($_GET['image'])){
  $image  = base64_decode($_GET['image']);
  header("Content-type: image/jpeg");
  header("Cache-Control: no-store, no-cache");
  $file   = $cfg['img']['dir'].'/'.base64_decode($_GET['image']);
  $result = ftp_get($ftp_conn, "php://output", $file, FTP_BINARY);
  exit($result);
}

$file_list = ftp_get_filelist($ftp_conn, $cfg['img']['dir']);

ftp_close($ftp_conn);
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title></title>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css"/>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <style media="screen">
      .container{margin-top:25px;}
      .set-url{margin-top: 25px;}
      #url{width: 100%;}
    </style>
  </head>
  <body>
    <div class="container">
      <div class="table-responsive">
        <table id="images" class="table table-striped table-bordered table-hover table-condensed table-sortable">
          <thead>
            <th>Player</th>
            <th>Image</th>
            <th>Time</th>
          </thead>
          <tbody>
            <?php
              $i = 0;
              foreach($file_list as $item){
                if(mb_strtolower($item['type']) == 'file'){
                  $newImageName = base64_encode($item['name']);
                  $newPlayerName = getPlayerName($item['name']);
                  echo '<tr>
                          <td><a id="getImage-'.$i++.'" href="#" player="'.$newPlayerName.'" image="'.$newImageName.'" data-toggle="modal" data-target="#showImage">'.$newPlayerName.'</a></td>
                          <td><a id="getImage-'.$i++.'" href="#" player="'.$newPlayerName.'" image="'.$newImageName.'" data-toggle="modal" data-target="#showImage">'.$item['name'].'</a></td>
                          <td>'.$item['date_modified'].'</td>
                        </tr>';
                }
              }
            ?>
          </tbody>
        </table>
      </div>

      <div id="showImage" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal">&times;</button>
              <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
              <div class="set-image"></div>
              <div class="set-url"><input id="url" value=""/></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>

    <script type="text/javascript">
      "use strict";jQuery.base64=(function($){var _PADCHAR="=",_ALPHA="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",_VERSION="1.0";function _getbyte64(s,i){var idx=_ALPHA.indexOf(s.charAt(i));if(idx===-1){throw"Cannot decode base64"}return idx}function _decode(s){var pads=0,i,b10,imax=s.length,x=[];s=String(s);if(imax===0){return s}if(imax%4!==0){throw"Cannot decode base64"}if(s.charAt(imax-1)===_PADCHAR){pads=1;if(s.charAt(imax-2)===_PADCHAR){pads=2}imax-=4}for(i=0;i<imax;i+=4){b10=(_getbyte64(s,i)<<18)|(_getbyte64(s,i+1)<<12)|(_getbyte64(s,i+2)<<6)|_getbyte64(s,i+3);x.push(String.fromCharCode(b10>>16,(b10>>8)&255,b10&255))}switch(pads){case 1:b10=(_getbyte64(s,i)<<18)|(_getbyte64(s,i+1)<<12)|(_getbyte64(s,i+2)<<6);x.push(String.fromCharCode(b10>>16,(b10>>8)&255));break;case 2:b10=(_getbyte64(s,i)<<18)|(_getbyte64(s,i+1)<<12);x.push(String.fromCharCode(b10>>16));break}return x.join("")}function _getbyte(s,i){var x=s.charCodeAt(i);if(x>255){throw"INVALID_CHARACTER_ERR: DOM Exception 5"}return x}function _encode(s){if(arguments.length!==1){throw"SyntaxError: exactly one argument required"}s=String(s);var i,b10,x=[],imax=s.length-s.length%3;if(s.length===0){return s}for(i=0;i<imax;i+=3){b10=(_getbyte(s,i)<<16)|(_getbyte(s,i+1)<<8)|_getbyte(s,i+2);x.push(_ALPHA.charAt(b10>>18));x.push(_ALPHA.charAt((b10>>12)&63));x.push(_ALPHA.charAt((b10>>6)&63));x.push(_ALPHA.charAt(b10&63))}switch(s.length-imax){case 1:b10=_getbyte(s,i)<<16;x.push(_ALPHA.charAt(b10>>18)+_ALPHA.charAt((b10>>12)&63)+_PADCHAR+_PADCHAR);break;case 2:b10=(_getbyte(s,i)<<16)|(_getbyte(s,i+1)<<8);x.push(_ALPHA.charAt(b10>>18)+_ALPHA.charAt((b10>>12)&63)+_ALPHA.charAt((b10>>6)&63)+_PADCHAR);break}return x.join("")}return{decode:_decode,encode:_encode,VERSION:_VERSION}}(jQuery));
    </script>
    <script type="text/javascript">
      $(document).ready(function() {
        $('a[id^="getImage"]').click(function(e){
          var image    = '';
          var decImage = '';
          var getImage = '';
          $('.modal-title').html('');
          $('.set-image').html('Loading...');
          $('.set-image').attr('value', '');

          image = $(this).attr('image');
          player = $(this).attr('player');
          decImage = $.base64.decode(image)
          getImage = '?image=' + image;
          $('.modal-title').text('Player: ' + player);
          $('.set-image').html('<img style="max-width:100%;" src="/'+getImage+'">');
          $('input#url').attr('value', '<?=$actual_link?>'+getImage);
        });
      });
    </script>
  </body>
</html>
