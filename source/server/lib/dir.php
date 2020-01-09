<?php
namespace fmihel\router\lib;

define('_DIR_SEPARATOR','/');

class Dir{
    
    static function slash($dir,$left,$right){
        if (trim($dir)=='') return '';
        /*учитываем вариант когда точка находится в имени корневой папки*/
        $root =  substr($_SERVER['DOCUMENT_ROOT'],strrpos($_SERVER['DOCUMENT_ROOT'],'/')+1);

        $dirs = explode(_DIR_SEPARATOR,trim($dir));
        $out = '';
        
        $is_dos = false;
        //print_r($dirs);
        //echo '<br>';
        if (count($dirs)>0){
					$s = $dirs[0];
					$is_dos = (substr($s,strlen($s)-1)==':');
				}	
        
        for($i=0;$i<count($dirs);$i++)
            $out.=(strlen($dirs[$i])>0?(strlen($out)>0?_DIR_SEPARATOR:'').$dirs[$i]:'');
        
        $last=$dirs[count($dirs)-1];
        return ((($left)&&(!$is_dos))?_DIR_SEPARATOR:'').$out.(($right)&&(($last==$root)||(!strpos($last,'.')))?_DIR_SEPARATOR:'');
    }
    
    static function pathinfo($file){
        $out = array('file'=>$file,'dirname'=>'','basename'=>'','extension'=>'','filename'=>'');
        $slash = '/';
        //------------------------------------------------
        $have_oslash = (mb_strpos($file,'\\')!==false);
        if ($have_oslash)
            $file = str_replace('\\',$slash,$file);
        //------------------------------------------------

        $lim = mb_strrpos($file,$slash);
        if ($lim!==false){
            $left = mb_substr($file,0,$lim);
            $right= mb_substr($file,$lim+1);
            
            $out['dirname'] = $left;
            $out['basename'] = $right;
            $out['filename'] = $right;
            
            $pos_ext = mb_strrpos($right,'.');
            if ($pos_ext!==false){
                $out['extension'] = mb_substr($right,$pos_ext+1);
                $out['filename'] = mb_substr($right,0,$pos_ext);
            }
            
        }else{
            $out['basename'] = $file;
            $out['filename'] = $file;
            
            $pos_ext = mb_strrpos($file,'.');
            if ($pos_ext!==false){
                $out['extension'] = mb_substr($file,$pos_ext+1);
                $out['filename'] = mb_substr($file,0,$pos_ext);
            };
            
        }

        //------------------------------------------------
        if ($have_oslash)
            foreach($out as $k=>$v)
                $out[$k] = str_replace($slash,'\\',$v);
        //------------------------------------------------
        
        return $out;
    }

    static function ext($file){
        //S: получите расширение файла
        $path = self::pathinfo($file);
        return $path['extension'];
    }
    
    private static function _exts($exts){
        //------------------------------------------------
        if (!is_array($exts)){
            $_ext=explode(',',$exts);
            $ext=array();
            for($i=0;$i<count($_ext);$i++){
                if(trim($_ext[$i])!=='')
                    array_push($ext,trim($_ext[$i]));
            };
        }else
            $ext=$exts;
        //------------------------------------------------
        //upper ext    
        for($i=0;$i<count($ext);$i++)
            $ext[$i] = strtoupper($ext[$i]);
        return $ext;
    }
    
    static function struct($path,$exts=array(),$only_dir=false,$level=10000,$_root=''){
        /*return file_struct begin from $path
        $res = array(
            array(  'name' - short name  Ex: menu
                    'path' - path from begin $path Ex: ws/inter/menu/
                    'is_file' - true if file
                    childs = array(...) - childs dir (if is_file = false :)
            )    
        )
        */
        
        $res = array();
        if ($_root=='') $_root=self::slash($path,false,true);
        //------------------------------------------------
        $ext = self::_exts($exts);    
        //------------------------------------------------
        // add directory
        $dir = scandir($path);
        for($i=0;$i<count($dir);$i++){
            $item = $dir[$i];
            if (($item!=='.')&&($item!=='..')){
                $item_path = self::slash($path,false,false).self::slash($item,true,false);
            
                if (is_dir($item_path)){
                    
                    
                    array_push($res,array(
                        'name'=>$item,
                        //'path'=>APP::abs_path($_root,$item_path),
                        'path'=>substr($item_path,strlen($_root)),
                        'is_file'=>false,
                        'childs'=>($level<=0?array():self::struct($item_path.'/',$ext,$only_dir,$level-1,$_root))));
                }
            }
        }
    
        // add files
        if (!$only_dir)
        for($i=0;$i<count($dir);$i++){
            $item = $dir[$i];
            if (($item!=='.')&&($item!=='..')){
                $item_file = self::slash($path,false,false).self::slash($item,true,false);
            
                if (is_file($item_file)){
                    $_ext = strtoupper(self::ext($item));
                    if ((count($ext)==0)||(in_array($_ext,$ext)))
                    array_push($res,array(
                        'name'=>$item,
                        //'path'=>APP::abs_path($_root,$item_file),
                        'path'=>substr($item_file,strlen($_root)),
                        'is_file'=>true));
                }
            }
        }

        return $res;
    }
    
    static function _lstruct($struct,&$to){
        
        for($i=0;$i<count($struct);$i++){
            $el=$struct[$i];
            if ($el['is_file']){
                $add = array();
                foreach($el as $k=>$v){
                    if ($k!=='childs')
                        $add[$k]=$v;
                }
                array_push($to,$add);
            }
        }
        for($i=0;$i<count($struct);$i++){
            $el=$struct[$i];
            if (!$el['is_file']){
                //$add = array();
                //foreach($el as $k=>$v){
                //    if ($k!=='childs')
                //        $add[$k]=$v;
            //    }
            //    array_push($to,$add);
                self::_lstruct($el['childs'],$to);
            }
        }
        
    }    
    
    static function lstruct($path,$exts=array()){
        $struct = self::struct($path,$exts);
        $out = array();
        for($i=0;$i<count($struct);$i++){
            $el=$struct[$i];
            if ($el['is_file']){
                $add = array();
                foreach($el as $k=>$v){
                    if ($k!=='childs')
                        $add[$k]=$v;
                }
                array_push($out,$add);
            }
        }
        
        for($i=0;$i<count($struct);$i++){
            $el=$struct[$i];
            if (!$el['is_file']){
                //$add = array();
                //foreach($el as $k=>$v){
                //    if ($k!=='childs')
                //        $add[$k]=$v;
                //}
                //array_push($out,$add);
                self::_lstruct($el['childs'],$out);
            }
        }
        return $out;
    }    
    
    static function files($path,$exts='',$full_path=false,$only_root=true){
        
        //echo 'path:  '.$path."\n";        
        
        $struct     =   self::struct($path,$exts,false,0);
        $full_path  =   ($only_root?$full_path:true);


        $res        =   array();
        
        for($i=0;$i<count($struct);$i++){
            $item = $struct[$i];
            if ($item['is_file']){
                //array_push($res,($full_path?$item['path']:$item['name']));
                array_push($res,($full_path?$path:'').$item['name']);    
            }    
        }
        
        $dirs       =   ($only_root?array():self::dirs($path,true));
        for($i=0;$i<count($dirs);$i++){
            
            $next_path = $path.$dirs[$i].'/';

            $out = self::files($next_path,$exts,true,false);
            for($j=0;$j<count($out);$j++)
                array_push($res,$out[$j]);    
        }
        return $res;
    }
    
    static function dirs($path,$full_path=false){
        $struct = self::struct($path,'',true,0);
        $res = array();
        for($i=0;$i<count($struct);$i++){
            $item = $struct[$i];
            if (!$item['is_file'])
                array_push($res,($full_path?$item['path']:$item['name']));    
        }
        return $res;
    }
    /**
     * clear folder
     * $path is relation path to clear path ( delete all inside in $path,widthout $path)
     * example
     * you app place in:   home/ubuntu/www/app/test01/index.php
     * need clear folder:  home/ubuntu/www/aaa/bbb/
     * use next:
     * $path =  APP::slash(APP::rel_path($Application->PATH,$Application->ROOT.'aaa/bbb/'));
     * self::clear($path)
     * 
    */ 
    static function clear($path){
        
        $files = self::files($path,'',false);
        $dirs  = self::dirs($path,false);
        
        for($i=0;$i<count($files);$i++)
            unlink($path.$files[$i]);

        for($i=0;$i<count($dirs);$i++){
            self::clear(self::slash($path.$dirs[$i],false,true));
            rmdir($path.$dirs[$i]);
        };    
    }
    
    static function info($dir){
        $exist = file_exists($dir);
        
        if ($exist){
            $is_dir = is_dir($dir);
            $is_file = !$is_dir;
        }else{
            $is_dir = false;
            $is_file = false;
        };    
        
        return array('exist'=>$exist,'is_dir'=>$is_dir,'is_file'=>$is_file);
    }
     /**
     * проверка существовния папки
     */ 
    static function exist($dir){
        return (file_exists($dir) && is_dir($dir));
    }
    
    /**
     * копирует папку
    */
    static function copy($src,$dst,$stopOnError = false) { 
        $res = true;
        
        if (!self::exist($src)) return false;
        
        $dir = opendir($src);
        
        if ($dir!==false){
            @mkdir($dst); 
            while(false !== ( $file = readdir($dir)) ) { 
                if (( $file != '.' ) && ( $file != '..' )) { 
                    
                    if ( is_dir($src . '/' . $file) ){ 
                        if (!self::copy($src . '/' . $file,$dst . '/' . $file,$stopOnError))
                            $res = false;
                    }else{ 
                        if (!copy($src . '/' . $file,$dst . '/' . $file))
                            $res = false;
                    }
                    
                    if ((!$res)&&($stopOnError))
                        break;
                } 
            } 
            closedir($dir); 
            
        }else
            return false;
            
        return $res;    
    }
    
    

};//class DIRS

?>