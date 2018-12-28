<?php
/**
 * Created by PhpStorm.
 * User: gzq
 * Date: 2018/12/27
 * Time: 10:16
 * 文件上传函数
 */
/**
 * 多文件上传显示源文件名称,点击文件名在新页面中打开
 * @param array $source_data 已经有的数据
 * @param array $other_config 其他配置项
 * @param string $input_name 获取的input名称
 * @param string $upload_name 前面的上传名称提示
 * @param string $div_width 上传范围大小
 * @return string
 *
 *
 * 样式调整  layui-upload-self
 */
function FileUploadUseSourceName($source_data = array(),$other_config = array(
),$input_name = '',$upload_name = '文件选择',$div_width = '800'){
    //获取当前让上传的最大文件大小
    //基础配置
    $other_config = array_merge(array(
        'ajax_data' => '{}',//ajax 发送的数据
        'show_upload_div' => 'demoList',//上传后展示的div id
        'click_button' => 'testList',//点击选择button的id
        'upload_button' => 'testListAction',//上传按钮button的id
        'upload_type' => 'file',//上传类型【images（图片）、file（所有文件）、video（视频）、audio（音频）】
        'upload_url' => '',//上传处理的url
        'max_size' => bcmul(floatval(ini_get('post_max_size')),1024),//最大上传大小
        'allow_ext' => array('jpeg','jpg','png','pdf','doc','docx','xls','xlsx','txt','csv')//允许的类型
    ),$other_config);
    //获取数据的mine
    $allow_ext = FileUploadExtToMine($other_config['allow_ext']);

    $mine_ext = json_encode(array_values($allow_ext),JSON_UNESCAPED_UNICODE);

    $list_source_data = function ($source_data,$input_name) {
        if (empty($source_data)) {
            return '';
        }
        $html = '';
        foreach ($source_data as $key => $value) {
            $html .= <<<EOF
                    <tr>
                    <td>
                    <a href="{$value['path']}" target="_blank">{$value['source_name']}</a>
                    <input type="hidden" value="{$value['source']}" name="{$input_name}"/>
                    </td>
                    <td>---</td>
                    <td><span style="color: #FF5722;">上一次数据</span></td>
                    <td><button class="layui-btn layui-btn-xs layui-btn-danger demo-delete">删除</button></td>
                    </tr>
EOF;
        };
        return $html;
    };

    $html = <<<EOF
<!--引入layer弹出框插件-->
<script src="/images/jquery_layer/layui.js" type="text/javascript"></script>
<link href="/images/jquery_layer/css/layui.css" rel="stylesheet" type="text/css" />
<div class="layui-upload layui-upload-self">
						<button type="button" class="layui-btn layui-btn-normal" style="display: inline-block" id="{$other_config['click_button']}">$upload_name</button>
						<button type="button" class="layui-btn" id="{$other_config['upload_button']}" style="display: inline-block">开始上传</button>
						<div class="layui-upload-list" style="width: {$div_width}px">
							<table class="layui-table">
								<thead>
								<tr><th>文件名</th>
									<th>大小</th>
									<th>状态</th>
									<th>操作</th>
								</tr></thead>
								<tbody id="{$other_config['show_upload_div']}">
						            {$list_source_data($source_data,$input_name)}
								</tbody>
							</table>
						</div>
					</div>
<script type="text/javascript">
        layui.use('upload', function(){
            var $ = layui.jquery
                ,upload = layui.upload;


            //多文件列表示例
            var demoListView = $("#{$other_config['show_upload_div']}")
                ,uploadListIns = upload.render({
                elem: "#{$other_config['click_button']}"
                ,url: "{$other_config['upload_url']}"
                ,accept: '{$other_config['upload_type']}'
                ,acceptMime:{$mine_ext}
                ,multiple: true
                ,auto: false,
                size:"{$other_config['max_size']}",
                data:{$other_config['ajax_data']}
                ,bindAction: "#{$other_config['upload_button']}"
                ,choose: function(obj){
                    var files = this.files = obj.pushFile(); //将每次选择的文件追加到文件队列
                    //读取本地文件
                    obj.preview(function(index, file, result){
                        var tr = $(['<tr id="upload-'+ index +'">'
                            ,'<td>'+ file.name +'</td>'
                            ,'<td>'+ (file.size/1014).toFixed(1) +'kb</td>'
                            ,'<td>等待上传</td>'
                            ,'<td>'
                            ,'<button class="layui-btn layui-btn-xs demo-reload layui-hide">重传</button>'
                            ,'<button class="layui-btn layui-btn-xs layui-btn-danger demo-delete">删除</button>'
                            ,'</td>'
                            ,'</tr>'].join(''));

                        //单个重传
                        tr.find('.demo-reload').on('click', function(){
                            obj.upload(index, file);
                        });

                        //删除
                        tr.find('.demo-delete').on('click', function(){
                            delete files[index]; //删除对应的文件
                            tr.remove();
                            uploadListIns.config.elem.next()[0].value = ''; //清空 input file 值，以免删除后出现同名文件不可选
                        });

                        demoListView.append(tr);
                    });
                }
                ,done: function(res, index, upload){
                    if(res.errorno == 0){ //上传成功
                        var tr = demoListView.find('tr#upload-'+ index)
                            ,tds = tr.children();
                        var fileName = tds.eq(0).html();
                        tds.eq(0).html('<a href="'+res.data2+'" target="_blank">'+fileName+'</a>' +
                            '<input type="hidden" value="'+res.data1+'|'+fileName+'" name="{$input_name}">');
                        tds.eq(2).html('<span style="color: #5FB878;">上传成功</span>');
                        //tds.eq(3).html(''); //清空操作
                        return delete this.files[index]; //删除文件队列已经上传成功的文件
                    }
                    this.error(res,index, upload);
                }
                ,error: function(res,index, upload){
                    var tr = demoListView.find('tr#upload-'+ index)
                        ,tds = tr.children();
                    tds.eq(2).html('<span style="color: #FF5722;">上传失败:'+res.data1+'</span>');
                    //tds.eq(3).find('.demo-reload').removeClass('layui-hide'); //显示重传
                }
            });
            });
            </script>
EOF;
    return $html;
}

/**
 * 列表显示
 * @param array $source_arr 处理好的数据
 * @param array $other_config 一些配置项，需要添加
 * @return string
 */
function FileShowUseSourceName($source_arr = array(),$other_config = array()){
    if(empty($source_arr)){
        return '';
    }
    $other_config = array_merge(array("width"=>600),$other_config);
    $html = <<<EOF
    <link href="/images/jquery_layer/css/layui.css" rel="stylesheet" type="text/css" />
<div class="layui-upload-list layui-upload-self" style="width: {$other_config['width']}px;">
					<table class="layui-table">
						<tbody id="demoList">
EOF;
;
    foreach ($source_arr as $key => $value){
        $html .=<<<EOF
<tr>
							<td>
								<a href="{$value['path']}" target="_blank">{$value['source_name']}</a>
							</td>
						</tr>
EOF;
    }
    $html .= <<<EOF
</tbody>
					</table>
				</div>
EOF;

    return $html;
}
/**
 * 解析存好的数据结构   上传文件路劲|源文件名,....
 * @param string $source_str 数据结构
 * @return array 处理好的数据
 */
function AnalysisUseSourceNameData($source_str = ''){
    if(empty($source_str)){
        return array();
    }
    $source_arr = json_decode(htmlspecialchars_decode($source_str,ENT_QUOTES),1);
    $source_arr = array_reduce($source_arr,function($result,$item){
        $temp_item = explode('|',$item);
        $result[] = array(
            'source' => $item,
            'source_name' => $temp_item[1],
            'path' => ReturnFileUrl($temp_item[0])
        );
        return $result;
    });
    return $source_arr;
}

/**
 * 上传文件
 * @param string $upload_type  上传类型  $uploadPath中的key
 * @param string $object_name 上传文件名称 file中的name值
 * @return array
 */
function UploadFileNoLimitType($upload_type,$object_name){
    if(empty($upload_type) || empty($object_name)){
        return array(-1,'上传文件必要参数不能为空');
    }
    $uploadPath=array(
        //安全评估文件
        "safe_assess_file"				=>array("path"=>FileUploadsFolder."/safe_assess_file",'max_size' => 5,'allow_ext' => array('jpeg','jpg','png','pdf','doc','docx','xls','xlsx','txt','csv')),
    );

    if(!array_key_exists($upload_type,$uploadPath)){
        return array(-1,'上传文件类型不能不存在');
    }

    $config_arr = $uploadPath[$upload_type];

    $fileinfo=$_FILES[$object_name];
    if(empty($fileinfo)){
        return array(-1,"请选择需要上传的文件！");
    }
    //文件类型判断
    $allow_ext = FileUploadExtToMine($config_arr['allow_ext']);
    //文件扩展名
    $ext = pathinfo($fileinfo['name'], PATHINFO_EXTENSION);
    if(!in_array($fileinfo['type'],$allow_ext) || !array_key_exists($ext,$allow_ext)){
        return array(-1,"文件类型不是允许的类型！");
    }
    //判断是否有error代码错误
    if(!empty($fileinfo['error'])){
        $uploadError=array(
            '1'=>'上传文件大小过大！',							//上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。
            '2'=>'上传的文件大小超过了指定的值！',	//上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。
            '3'=>'文件只有部分被上传！',
            '4'=>'没有文件被上传！',
            '6'=>'系统错误，上传有误！',					//找不到临时文件夹。PHP 4.3.10 和 PHP 5.0.3 引进。
            '7'=>'系统错误，上传有误！'						//文件写入失败。PHP 5.1.0 引进。
        );
        $errormsg=isset($uploadError[$fileinfo['error']]) ? $uploadError[$fileinfo['error']] : "文件上传错误，错误码：{$fileinfo['error']}";
        return array(-1,$errormsg);
    }
    //判断图片大小
    if($fileinfo['size']>$config_arr['max_size']*1024*1024){
        return array(-1,"只能上传小于".($config_arr['max_size']*1024)."KB的文件！");
    }
    //判断图片是否是通过上传而来的，表示判断是否是使用HTTP POST方式上传而来的
    if(!is_uploaded_file($fileinfo['tmp_name'])){
        return array(-1,"请使用正确的方式上传文件！");
    }
    //病毒图片检查
    if(in_array($ext,array('jpeg','png','jpg')) && FileUploadCheckPicVirus($fileinfo['tmp_name'])){
        return array(-1,"文件疑似为病毒文件，无法上传！");
    }


    //移动文件到新的地方
    $basePath = ReturnImageUploadBasePath();//image.x7sy.com
    if(!is_dir($basePath)){
        mkdir($basePath,FolderMode,true);
    }
    $relPath="/".$config_arr['path']."/".date("Ymd",time());

    if(!is_dir($basePath.$relPath)){
        mkdir($basePath.$relPath,FolderMode,true);
    }

    //文件名、文件路径，相对文件路径和绝对文件路径
    $imageFileName=mt_rand(100000,999999).mt_rand(100000,999999).substr(time(),-5).".".$ext;
    $relFilePath=$relPath."/".$imageFileName;
    $absPath=$basePath.$relFilePath;

    //移动文件到新位置上
    if(!move_uploaded_file($fileinfo['tmp_name'],$absPath)){
        return array(-1,"文件移动失败！");
    }
    if(in_array($ext,array('jpeg','png','jpg'))){
        imageRedraw($ext,$absPath);//图片重绘
    }
    //上传文件到阿里云OSS
    $ret=uploadFileToOss(ltrim($relFilePath,'/'),$absPath);
    if($ret==false) return array(-1,"文件上传阿里云OSS失败！");
    return array(0,$relFilePath);
}
function FileUploadMineToExt()
{
    $operate_arr = array(
        'application/envoy' => 'evy'
        , 'application/fractals' => 'fif'
        , 'application/futuresplash' => 'spl'
        , 'application/hta' => 'hta'
        , 'application/internet-property-stream' => 'acx'
        , 'application/mac-binhex40' => 'hqx'
        , 'application/msword' => 'doc'
        , 'application/msword' => 'dot'
        , 'application/octet-stream' => '*'
        , 'application/octet-stream' => 'bin'
        , 'application/octet-stream' => 'class'
        , 'application/octet-stream' => 'dms'
        , 'application/octet-stream' => 'exe'
        , 'application/octet-stream' => 'lha'
        , 'application/octet-stream' => 'lzh'
        , 'application/oda' => 'oda'
        , 'application/olescript' => 'axs'
        , 'application/pdf' => 'pdf'
        , 'application/pics-rules' => 'prf'
        , 'application/pkcs10' => 'p10'
        , 'application/pkix-crl' => 'crl'
        , 'application/postscript' => 'ai'
        , 'application/postscript' => 'eps'
        , 'application/postscript' => 'ps'
        , 'application/rtf' => 'rtf'
        , 'application/set-payment-initiation' => 'setpay'
        , 'application/set-registration-initiation' => 'setreg'
        , 'application/vnd.ms-excel' => 'xla'
        , 'application/vnd.ms-excel' => 'xlc'
        , 'application/vnd.ms-excel' => 'xlm'
        , 'application/vnd.ms-excel' => 'xls'
        , 'application/vnd.ms-excel' => 'xlt'
        , 'application/vnd.ms-excel' => 'xlw'
        , 'application/vnd.ms-outlook' => 'msg'
        , 'application/vnd.ms-pkicertstore' => 'sst'
        , 'application/vnd.ms-pkiseccat' => 'cat'
        , 'application/vnd.ms-pkistl' => 'stl'
        , 'application/vnd.ms-powerpoint' => 'pot'
        , 'application/vnd.ms-powerpoint' => 'pps'
        , 'application/vnd.ms-powerpoint' => 'ppt'
        , 'application/vnd.ms-project' => 'mpp'
        , 'application/vnd.ms-works' => 'wcm'
        , 'application/vnd.ms-works' => 'wdb'
        , 'application/vnd.ms-works' => 'wks'
        , 'application/vnd.ms-works' => 'wps'
        , 'application/winhlp' => 'hlp'
        , 'application/x-bcpio' => 'bcpio'
        , 'application/x-cdf' => 'cdf'
        , 'application/x-compress' => 'z'
        , 'application/x-compressed' => 'tgz'
        , 'application/x-cpio' => 'cpio'
        , 'application/x-csh' => 'csh'
        , 'application/x-director' => 'dcr'
        , 'application/x-director' => 'dir'
        , 'application/x-director' => 'dxr'
        , 'application/x-dvi' => 'dvi'
        , 'application/x-gtar' => 'gtar'
        , 'application/x-gzip' => 'gz'
        , 'application/x-hdf' => 'hdf'
        , 'application/x-internet-signup' => 'ins'
        , 'application/x-internet-signup' => 'isp'
        , 'application/x-iphone' => 'iii'
        , 'application/x-javascript' => 'js'
        , 'application/x-latex' => 'latex'
        , 'application/x-msaccess' => 'mdb'
        , 'application/x-mscardfile' => 'crd'
        , 'application/x-msclip' => 'clp'
        , 'application/x-msdownload' => 'dll'
        , 'application/x-msmediaview' => 'm13'
        , 'application/x-msmediaview' => 'm14'
        , 'application/x-msmediaview' => 'mvb'
        , 'application/x-msmetafile' => 'wmf'
        , 'application/x-msmoney' => 'mny'
        , 'application/x-mspublisher' => 'pub'
        , 'application/x-msschedule' => 'scd'
        , 'application/x-msterminal' => 'trm'
        , 'application/x-mswrite' => 'wri'
        , 'application/x-netcdf' => 'cdf'
        , 'application/x-netcdf' => 'nc'
        , 'application/x-perfmon' => 'pma'
        , 'application/x-perfmon' => 'pmc'
        , 'application/x-perfmon' => 'pml'
        , 'application/x-perfmon' => 'pmr'
        , 'application/x-perfmon' => 'pmw'
        , 'application/x-pkcs12' => 'p12'
        , 'application/x-pkcs12' => 'pfx'
        , 'application/x-pkcs7-certificates' => 'p7b'
        , 'application/x-pkcs7-certificates' => 'spc'
        , 'application/x-pkcs7-certreqresp' => 'p7r'
        , 'application/x-pkcs7-mime' => 'p7c'
        , 'application/x-pkcs7-mime' => 'p7m'
        , 'application/x-pkcs7-signature' => 'p7s'
        , 'application/x-sh' => 'sh'
        , 'application/x-shar' => 'shar'
        , 'application/x-shockwave-flash' => 'swf'
        , 'application/x-stuffit' => 'sit'
        , 'application/x-sv4cpio' => 'sv4cpio'
        , 'application/x-sv4crc' => 'sv4crc'
        , 'application/x-tar' => 'tar'
        , 'application/x-tcl' => 'tcl'
        , 'application/x-tex' => 'tex'
        , 'application/x-texinfo' => 'texi'
        , 'application/x-texinfo' => 'texinfo'
        , 'application/x-troff' => 'roff'
        , 'application/x-troff' => 't'
        , 'application/x-troff' => 'tr'
        , 'application/x-troff-man' => 'man'
        , 'application/x-troff-me' => 'me'
        , 'application/x-troff-ms' => 'ms'
        , 'application/x-ustar' => 'ustar'
        , 'application/x-wais-source' => 'src'
        , 'application/x-x509-ca-cert' => 'cer'
        , 'application/x-x509-ca-cert' => 'crt'
        , 'application/x-x509-ca-cert' => 'der'
        , 'application/ynd.ms-pkipko' => 'pko'
        , 'application/zip' => 'zip'
        , 'audio/basic' => 'au'
        , 'audio/basic' => 'snd'
        , 'audio/mid' => 'mid'
        , 'audio/mid' => 'rmi'
        , 'audio/mpeg' => 'mp3'
        , 'audio/x-aiff' => 'aif'
        , 'audio/x-aiff' => 'aifc'
        , 'audio/x-aiff' => 'aiff'
        , 'audio/x-mpegurl' => 'm3u'
        , 'audio/x-pn-realaudio' => 'ra'
        , 'audio/x-pn-realaudio' => 'ram'
        , 'audio/x-wav' => 'wav'
        , 'image/bmp' => 'bmp'
        , 'image/cis-cod' => 'cod'
        , 'image/gif' => 'gif'
        , 'image/ief' => 'ief'
        , 'image/jpeg' => 'jpe'
        , 'image/jpeg' => 'jpeg'
        , 'image/jpeg' => 'jpg'
        , 'image/pipeg' => 'jfif'
        , 'image/svg+xml' => 'svg'
        , 'image/tiff' => 'tif'
        , 'image/tiff' => 'tiff'
        , 'image/x-cmu-raster' => 'ras'
        , 'image/x-cmx' => 'cmx'
        , 'image/x-icon' => 'ico'
        , 'image/x-portable-anymap' => 'pnm'
        , 'image/x-portable-bitmap' => 'pbm'
        , 'image/x-portable-graymap' => 'pgm'
        , 'image/x-portable-pixmap' => 'ppm'
        , 'image/x-rgb' => 'rgb'
        , 'image/x-xbitmap' => 'xbm'
        , 'image/x-xpixmap' => 'xpm'
        , 'image/x-xwindowdump' => 'xwd'
        , 'message/rfc822' => 'mht'
        , 'message/rfc822' => 'mhtml'
        , 'message/rfc822' => 'nws'
        , 'text/css' => 'css'
        , 'text/h323' => '323'
        , 'text/html' => 'htm'
        , 'text/html' => 'html'
        , 'text/html' => 'stm'
        , 'text/iuls' => 'uls'
        , 'text/plain' => 'bas'
        , 'text/plain' => 'c'
        , 'text/plain' => 'h'
        , 'text/plain' => 'txt'
        , 'text/richtext' => 'rtx'
        , 'text/scriptlet' => 'sct'
        , 'text/tab-separated-values' => 'tsv'
        , 'text/webviewhtml' => 'htt'
        , 'text/x-component' => 'htc'
        , 'text/x-setext' => 'etx'
        , 'text/x-vcard' => 'vcf'
        , 'video/mpeg' => 'mp2'
        , 'video/mpeg' => 'mpa'
        , 'video/mpeg' => 'mpe'
        , 'video/mpeg' => 'mpeg'
        , 'video/mpeg' => 'mpg'
        , 'video/mpeg' => 'mpv2'
        , 'video/quicktime' => 'mov'
        , 'video/quicktime' => 'qt'
        , 'video/x-la-asf' => 'lsf'
        , 'video/x-la-asf' => 'lsx'
        , 'video/x-ms-asf' => 'asf'
        , 'video/x-ms-asf' => 'asr'
        , 'video/x-ms-asf' => 'asx'
        , 'video/x-msvideo' => 'avi'
        , 'video/x-sgi-movie' => 'movie'
        , 'x-world/x-vrml' => 'flr'
        , 'x-world/x-vrml' => 'vrml'
        , 'x-world/x-vrml' => 'wrl'
        , 'x-world/x-vrml' => 'wrz'
        , 'x-world/x-vrml' => 'xaf'
        , 'x-world/x-vrml' => 'xof'
    );
}

/**
 * 全部的类型
 * @param array $allow_ext 允许上传的类型
 * @return array|mixed 获取到的类型mine
 */
function FileUploadExtToMine($allow_ext = array())
{
    if(empty($allow_ext)){
        return array();
    }
    $operate_arr = array(
        '*' => 'application/octet-stream'
        , '323' => 'text/h323'
        , 'acx' => 'application/internet-property-stream'
        , 'ai' => 'application/postscript'
        , 'aif' => 'audio/x-aiff'
        , 'aifc' => 'audio/x-aiff'
        , 'aiff' => 'audio/x-aiff'
        , 'asf' => 'video/x-ms-asf'
        , 'asr' => 'video/x-ms-asf'
        , 'asx' => 'video/x-ms-asf'
        , 'au' => 'audio/basic'
        , 'avi' => 'video/x-msvideo'
        , 'axs' => 'application/olescript'
        , 'bas' => 'text/plain'
        , 'bcpio' => 'application/x-bcpio'
        , 'bin' => 'application/octet-stream'
        , 'bmp' => 'image/bmp'
        , 'c' => 'text/plain'
        , 'cat' => 'application/vnd.ms-pkiseccat'
        , 'cdf' => 'application/x-cdf'
        , 'cer' => 'application/x-x509-ca-cert'
        , 'class' => 'application/octet-stream'
        , 'clp' => 'application/x-msclip'
        , 'cmx' => 'image/x-cmx'
        , 'cod' => 'image/cis-cod'
        , 'cpio' => 'application/x-cpio'
        , 'crd' => 'application/x-mscardfile'
        , 'crl' => 'application/pkix-crl'
        , 'crt' => 'application/x-x509-ca-cert'
        , 'csh' => 'application/x-csh'
        , 'css' => 'text/css'
        , 'csv' => 'application/octet-stream'
        , 'dcr' => 'application/x-director'
        , 'der' => 'application/x-director'
        , 'dll' => 'application/x-msdownload'
        , 'dms' => 'application/octet-stream'
        , 'doc' => 'application/msword'
        , 'docx'=> 'application/octet-stream'
        , 'dot' => 'application/msword'
        , 'dvi' => 'application/x-dvi'
        , 'dxr' => 'application/x-director'
        , 'eps' => 'application/postscript'
        , 'etx' => 'text/x-setext'
        , 'evy' => 'application/envoy'
        , 'exe' => 'application/octet-stream'
        , 'fif' => 'application/fractals'
        , 'flr' => 'x-world/x-vrml'
        , 'gif' => 'image/gif'
        , 'gtar' => 'application/x-gtar'
        , 'gz' => 'application/x-gzip'
        , 'h' => 'text/plain'
        , 'hdf' => 'application/x-hdf'
        , 'hlp' => 'application/winhlp'
        , 'hqx' => 'application/mac-binhex40'
        , 'hta' => 'application/hta'
        , 'htc' => 'text/x-component'
        , 'htm' => 'text/html'
        , 'html' => 'text/html'
        , 'htt' => 'text/webviewhtml'
        , 'ico' => 'image/x-icon'
        , 'ief' => 'image/ief'
        , 'iii' => 'application/x-iphone'
        , 'ins' => 'application/x-internet-signup'
        , 'isp' => 'application/x-internet-signup'
        , 'jfif' => 'image/pipeg'
        , 'jpe' => 'image/jpeg'
        , 'jpeg' => 'image/jpeg'
        , 'jpg' => 'image/jpeg'
        , 'js' => 'application/x-javascript'
        , 'latex' => 'application/x-latex'
        , 'lha' => 'application/octet-stream'
        , 'lsf' => 'video/x-la-asf'
        , 'lsx' => 'video/x-la-asf'
        , 'lzh' => 'application/octet-stream'
        , 'm13' => 'application/x-msmediaview'
        , 'm14' => 'application/x-msmediaview'
        , 'm3u' => 'audio/x-mpegurl'
        , 'man' => 'application/x-troff-man'
        , 'mdb' => 'application/x-msaccess'
        , 'me' => 'application/x-troff-me'
        , 'mht' => 'message/rfc822'
        , 'mhtml' => 'message/rfc822'
        , 'mid' => 'audio/mid'
        , 'mny' => 'application/x-msmoney'
        , 'mov' => 'video/quicktime'
        , 'movie' => 'video/x-sgi-movie'
        , 'mp2' => 'video/mpeg'
        , 'mp3' => 'audio/mpeg'
        , 'mpa' => 'video/mpeg'
        , 'mpe' => 'video/mpeg'
        , 'mpeg' => 'video/mpeg'
        , 'mpg' => 'video/mpeg'
        , 'mpp' => 'application/vnd.ms-project'
        , 'mpv2' => 'video/mpeg'
        , 'ms' => 'application/x-troff-ms'
        , 'mvb' => 'application/x-msmediaview'
        , 'nws' => 'message/rfc822'
        , 'oda' => 'application/oda'
        , 'p10' => 'application/pkcs10'
        , 'p12' => 'application/x-pkcs12'
        , 'p7b' => 'application/x-pkcs7-certificates'
        , 'p7c' => 'application/x-pkcs7-mime'
        , 'p7m' => 'application/x-pkcs7-mime'
        , 'p7r' => 'application/x-pkcs7-certreqresp'
        , 'p7s' => 'application/x-pkcs7-signature'
        , 'pbm' => 'image/x-portable-bitmap'
        , 'pdf' => 'application/pdf'
        , 'pfx' => 'application/x-pkcs12'
        , 'pgm' => 'image/x-portable-graymap'
        , 'pko' => 'application/ynd.ms-pkipko'
        , 'pma' => 'application/x-perfmon'
        , 'pmc' => 'application/x-perfmon'
        , 'pml' => 'application/x-perfmon'
        , 'pmr' => 'application/x-perfmon'
        , 'pmw' => 'application/x-perfmon'
        , 'pnm' => 'image/x-portable-anymap'
        , 'pot,' => 'application/vnd.ms-powerpoint'
        , 'ppm' => 'image/x-portable-pixmap'
        , 'pps' => 'application/vnd.ms-powerpoint'
        , 'ppt' => 'application/vnd.ms-powerpoint'
        , 'prf' => 'application/pics-rules'
        , 'ps' => 'application/postscript'
        , 'pub' => 'application/x-mspublisher'
        , 'png' => 'image/png'
        , 'qt' => 'video/quicktime'
        , 'ra' => 'audio/x-pn-realaudio'
        , 'ram' => 'audio/x-pn-realaudio'
        , 'ras' => 'image/x-cmu-raster'
        , 'rgb' => 'image/x-rgb'
        , 'rmi' => 'audio/mid'
        , 'roff' => 'application/x-troff'
        , 'rtf' => 'application/rtf'
        , 'rtx' => 'text/richtext'
        , 'scd' => 'application/x-msschedule'
        , 'sct' => 'text/scriptlet'
        , 'setpay' => 'application/set-payment-initiation'
        , 'setreg' => 'application/set-registration-initiation'
        , 'sh' => 'application/x-sh'
        , 'shar' => 'application/x-shar'
        , 'sit' => 'application/x-stuffit'
        , 'snd' => 'audio/basic'
        , 'spc' => 'application/x-pkcs7-certificates'
        , 'spl' => 'application/futuresplash'
        , 'src' => 'application/x-wais-source'
        , 'sst' => 'application/vnd.ms-pkicertstore'
        , 'stl' => 'application/vnd.ms-pkistl'
        , 'stm' => 'text/html'
        , 'svg' => 'image/svg+xml'
        , 'sv4cpio' => 'application/x-sv4cpio'
        , 'sv4crc' => 'application/x-sv4crc'
        , 'swf' => 'application/x-shockwave-flash'
        , 't' => 'application/x-troff'
        , 'tar' => 'application/x-tar'
        , 'tcl' => 'application/x-tcl'
        , 'tex' => 'application/x-tex'
        , 'texi' => 'application/x-texinfo'
        , 'texinfo' => 'application/x-texinfo'
        , 'tgz' => 'application/x-compressed'
        , 'tif' => 'image/tiff'
        , 'tiff' => 'image/tiff'
        , 'tr' => 'application/x-troff'
        , 'trm' => 'application/x-msterminal'
        , 'tsv' => 'text/tab-separated-values'
        , 'txt' => 'text/plain'
        , 'uls' => 'text/iuls'
        , 'ustar' => 'application/x-ustar'
        , 'vcf' => 'text/x-vcard'
        , 'vrml' => 'x-world/x-vrml'
        , 'wav' => 'audio/x-wav'
        , 'wcm' => 'application/vnd.ms-works'
        , 'wdb' => 'application/vnd.ms-works'
        , 'wks' => 'application/vnd.ms-works'
        , 'wmf' => 'application/x-msmetafile'
        , 'wps' => 'application/vnd.ms-works'
        , 'wri' => 'application/x-mswrite'
        , 'wrl' => 'x-world/x-vrml'
        , 'wrz' => 'x-world/x-vrml'
        , 'xaf' => 'x-world/x-vrml'
        , 'xbm' => 'image/x-xbitmap'
        , 'xla' => 'application/vnd.ms-excel'
        , 'xlc' => 'application/vnd.ms-excel'
        , 'xlm' => 'application/vnd.ms-excel'
        , 'xls' => 'application/vnd.ms-excel'
        , 'xlsx'=> 'application/octet-stream'
        , 'xlt' => 'application/vnd.ms-excel'
        , 'xlw' => 'application/vnd.ms-excel'
        , 'xof' => 'x-world/x-vrml'
        , 'xpm' => 'image/x-xpixmap'
        , 'xwd' => 'image/x-xwindowdump'
        , 'z' => 'application/x-compress'
        , 'zip' => 'application/zip'
    );

    return array_reduce($allow_ext,function ($result,$item) use ($operate_arr){
        if(array_key_exists($item,$operate_arr)){
            $result[$item] = $operate_arr[$item];
        }
        return $result;
    });

}

/**
 * 基础的检查图片中是否包含木马
 * @param string $path 文件路劲
 * @return bool true是木马，false不是
 */
function FileUploadCheckPicVirus($path){
    if(empty($path)){
        return false;
    }
    $resource = fopen($path,'rb');
    $file_size = filesize($path);
    //图片前512个字节为图片类型，不能一起转换
    if ($file_size > 512) { // 若文件大于521B文件取头和尾
        $hexCode = bin2hex(fread($resource, 512));
        fseek($resource, $file_size - 512);
        $hexCode .= bin2hex(fread($resource, 512));
    } else { // 取全部
        $hexCode = bin2hex(fread($resource, $file_size));
    }
    fclose($resource);
    /**
     * 对应的 hex值
     * <?           => 3c3f
     * <?php        => 3c3f706870
     * <%           => 3c25
     * ?>           => 3f3e
     * %>           => 253e
     * <script      =>3c736372697074
     * <script>     => 3c7363726970743e
     * </script>    => 3c2f7363726970743e
     *
     * 网上是这样的匹配规则：不太懂,但是可以检查出来
     * preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode)
     * */
    if(preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode)){
        return true;
    }
    return false;
}

/**
 * 字符串转换为16进制
 * @param string $str 转换的字符串
 * @return string 转换好的16进制的字符串
 */
function getStrHex($str){
    if(empty($str)){
        return '';
    }
    $out = "";
    for ($i = 0;$i<strlen($str);$i++){
        $out .=dechex(ord($str[$i]));
    }
    return $out;
}

/**
 * 图片重绘
 * @param string $ext 图片扩展名
 * @param string $path 文件路劲，源文件名称替换
 */
function imageRedraw($ext,$path){
    switch ($ext){
        case "jpg":
        case "jpeg":
            $img_source = imagecreatefromjpeg($path);
            imagejpeg($img_source,$path);
            break;
        case "png":
            $img_source = imagecreatefrompng($path);
            imagepng($img_source,$path);
            break;
        case "pdf":
            $img_source = imagecreatefromgif($path);
            imagegif($img_source,$path);
            break;
    }
}