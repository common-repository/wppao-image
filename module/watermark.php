<?php
/**
 * Class WPPAO_Watermark
 *  Yuanshu
 *  qq208125126
 *  @mryuanshu on github
 *  https://wppao.com
 */

/**
 * 文件夹生成
 * 防止出现错误
 */
function wppimg_wm_makedir(){
    $uploads = wp_upload_dir();
    $wppimg_dir = $uploads['basedir'].'/wppimg';
    if( !is_dir( $wppimg_dir ) ){
        mkdir( $wppimg_dir );
        mkdir( $wppimg_dir.'/fonts' );
    }
}

class WPPAO_Watermark{

    /**
     * WPPAO_Watermark constructor.
     * 不会真有人看吧 不会吧 不会吧？
     * 是不是？
     */
    function __construct(){
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'get_image_with_watermark' ), 9999 );
    }



    /**
     * @param string $options
     * @param array $args
     * 图片水印
     */
    function image_water( $options='',$args=array() ){
        //data
        $dst_file = $args['dst_file'];
        $src_file = $args['src_file'];
        $alpha = $args[ 'alpha' ];
        $position = $args['position'];
        $output_file= $args['output_file' ];

        $dst_data = @getimagesize( $dst_file );
        $dst_w = $dst_data[0];
        $dst_h = $dst_data[1];
        $min_w = isset( $options['wm_min_width'] ) && $options['wm_min_width'] ? $options['wm_min_width'] : 250 ;
        $min_h = isset( $options['wm_min_height'] ) && $options['wm_min_height'] ? $options['wm_min_height'] : 250 ;
        if( $dst_w <= $min_w || $dst_h <= $min_h ) return;
        $dst_mime = $dst_data['mime'];
        $src_data = @getimagesize( $src_file );
        $src_w = $src_data[0];
        $src_h = $src_data[1];
        $src_mime = $src_data['mime'];

        //create
        $dst = $this->create_image( $dst_file, $dst_mime );
        $src = $this->create_image( $src_file, $src_mime );
        $dst_xy = $this->position( $position, $src_w, $src_h, $dst_w, $dst_h );
        $merge = $this->imagecopymerge_alpha( $dst, $src, $dst_xy[0], $dst_xy[1], 0, 0, $src_w, $src_h, $alpha );
        if( $merge ){
            $this->make_image( $dst, $dst_mime, $output_file);
        }
        imagedestroy( $dst );
        imagedestroy( $src );
    }

    /**
     * @param string $options
     * @param array $args
     * 文字水印
     */
    function text_water( $options='', $args=array() ){
        //data
        $file = $args['file'];
        $font = $args['font'];
        $text = $args['text'];
        $alpha = $args['alpha'];
        $size = floatval($args['size']);
        $red = $args['color'][0];
        $green = $args['color'][1];
        $blue = $args['color'][2];
        $position = $args['position'];
        $output_file= $args['output_file'];

        $dst_data = @getimagesize( $file );
        $dst_w = $dst_data[0];
        $dst_h = $dst_data[1];
        $min_w = ( isset( $options['wm_min_width'] ) && $options['wm_min_width'] ) ? $options['wm_min_width'] : 250 ;
        $min_h = ( isset( $options['wm_min_height'] ) && $options['wm_min_height'] ) ? $options['wm_min_height'] : 250 ;
        if( $dst_w <= $min_w || $dst_h <= $min_h ) return;
        $dst_mime = $dst_data['mime'];

        //create
        $coord = imagettfbbox( $size, 0, $font, $text );
        $w = abs( $coord[2]-$coord[0] ) + 5;
        $h = abs( $coord[1]-$coord[7] ) ;
        $H = $h+$size/2;
        $src = $this->image_alpha( $w, $H );
        $color = imagecolorallocate( $src, $red, $green, $blue );
        $posion = imagettftext( $src, $size, 0, 0, $h, $color, $font, $text );
        $dst = $this->create_image( $file, $dst_mime );
        $dst_xy = $this->position( $position,$w, $H, $dst_w, $dst_h );
        $merge = $this->imagecopymerge_alpha( $dst, $src, $dst_xy[0], $dst_xy[1], 0, 0, $w, $H, $alpha );
        $this->make_image( $dst, $dst_mime, $output_file);
        imagedestroy( $dst );
        imagedestroy( $src );
    }

    /**
     * @param $file
     * @param $mime
     * @return false|resource
     * 生成
     */
    function create_image( $file, $mime ){
        switch( $mime ){
            case 'image/jpeg' : $im = imagecreatefromjpeg( $file ); break;
            case 'image/png' : $im = imagecreatefrompng( $file ); break;
            case 'image/gif' : $im = imagecreatefromgif( $file ); break;
        }
        return $im;
    }

    /**
     * @param $im
     * @param $mime
     * @param $im_file
     * 生成
     */
    function make_image( $im, $mime, $output_file){
        switch( $mime ){
            case 'image/jpeg' : {
                $options = get_option(WPPAO_IMAGE_KEY);
                $quality = ( isset( $options['wm_jpeg_qa'] ) && $options['wm_jpeg_qa'] ) ? $options['wm_jpeg_qa'] : 95;
                imagejpeg( $im, $output_file, $quality );
                break;
            }
            case 'image/png' : imagepng( $im, $output_file); break;
            case 'image/gif' : imagegif( $im, $output_file); break;
        }
    }

    /**
     * @param $dst_im
     * @param $src_im
     * @param $dst_x
     * @param $dst_y
     * @param $src_x
     * @param $src_y
     * @param $src_w
     * @param $src_h
     * @param $pct
     * @return bool
     * 水印通道
     */
    function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
        $opacity=$pct;

        $w = imagesx($src_im);
        $h = imagesy($src_im);

        $cut = imagecreatetruecolor($src_w, $src_h);
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        $merge = imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
        return $merge;
    }

    /**
     * @param $w
     * @param $h
     * @return false|resource
     * 水印透明
     */
    function image_alpha( $w, $h ){
        $im=imagecreatetruecolor( $w, $h );
        imagealphablending( $im, true );	//启用Alpha合成
        imageantialias( $im, true );	//启用抗锯齿
        imagesavealpha( $im, true );	//启用Alpha通道
        $bgcolor = imagecolorallocatealpha( $im,255,255,255,127 ); 		//创建透明颜色（最后一个参数0不透明，127完全透明）
        imagefill( $im, 0, 0, $bgcolor );//使图片底色透明
        return $im;
    }

    /**
     * @param $position
     * @param $s_w
     * @param $s_h
     * @param $d_w
     * @param $d_h
     * @return array
     * 水印位置自选哦
     */
    function position( $position, $s_w, $s_h, $d_w, $d_h ){
        switch( $position ){
            case 1 : $x=5; $y=0; break;
            case 2 : $x=($d_w-$s_w)/2; $y=0; break;
            case 3 : $x=($d_w-$s_w-5); $y=0; break;
            case 4 : $x=5; $y=($d_h-$s_h)/2; break;
            case 5 : $x=($d_w-$s_w)/2; $y=($d_h-$s_h)/2; break;
            case 6 : $x=($d_w-$s_w-5); $y=($d_h-$s_h)/2; break;
            case 7 : $x=5; $y=($d_h-$s_h); break;
            case 8 : $x=($d_w-$s_w)/2; $y=($d_h-$s_h); break;
            default: $x=($d_w-$s_w-5); $y=($d_h-$s_h); break;
        }
        $options = get_option(WPPAO_IMAGE_KEY);
        $x += $options['wm_x_just'];
        $y += $options['wm_y_just'];
        $xy = array( $x, $y );
        return $xy;
    }

    /**
     * @param $file
     * @return int
     * 动态不能瞎搞
     */
    function IsAnimatedGif( $file ){
        $content = file_get_contents($file);
        $bool = strpos($content, 'GIF89a');
        if($bool === FALSE)
        {
            return strpos($content, chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0')===FALSE?0:1;
        }
        else
        {
            return 1;
        }
    }

    /**
     * @param $str
     * @return array
     * HEX值
     */
    function hex_to_dec( $str ){
        $r = hexdec( substr( $str, 1, 2 ) );
        $g = hexdec( substr( $str, 3, 2 ) );
        $b = hexdec( substr( $str, 5, 2 ) );
        $color = array( $r, $g, $b );
        return $color;
    }

    /**
     * @param $metadata
     * @return mixed
     * 生成水印的说~
     */
    function get_image_with_watermark( $metadata){
        $options = get_option(WPPAO_IMAGE_KEY);

        $upload_dir = wp_upload_dir();
        $dst = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $metadata['file'];
        if( $this->IsAnimatedGif( $dst ) ) return $metadata;

        $src = $options['wm_image_content'];
        $size= $options['wm_text_size'] ? $options['wm_text_size'] : 16;
        $alpha= $options['wm_opacity'] ? $options['wm_opacity'] : 90;
        $position = $options['wm_position'] ? $options['wm_position'] : 9;
        $color = $options['wm_text_color'] ? $this->hex_to_dec( $options['wm_text_color'] ) : array(255,255,255);
        $font = $options['wm_text_font'] ? stripslashes($options['wm_text_font']) : WPPAO_IMAGE_DIR.'fonts/arial.ttf';
        $text = $options['wm_text_content'] ? stripslashes($options['wm_text_content']) : get_bloginfo('url');

        if( $options['wm_type']=='image' ){
            $args=array(
                'dst_file' => $dst,
                'src_file' => $src,
                'alpha' => $alpha,
                'position' => $position,
                'output_file' => $dst
            );
            $this->image_water( $options, $args );
        }
        else{
            $args=array(
                'file'=>$dst,
                'font'=>$font,
                'size'=>$size,
                'alpha'=>$alpha,
                'text'=>$text,
                'color'=>$color,
                'position'=>$position,
                'output_file' => $dst
            );
            $this->text_water( $options, $args );
        }
        return $metadata;
    }

    /**
     *
     */
    function get_preview_with_watermark()
    {
        $options = get_option(WPPAO_IMAGE_KEY);

        $dst = WPPAO_IMAGE_DIR.'imgs/watermark.jpg';

        $src = $options['wm_image_content'];
        $size = $options['wm_text_size'] ? $options['wm_text_size'] : 16;
        $alpha = $options['wm_opacity'] ? $options['wm_opacity'] : 90;
        $position = $options['wm_position'] ? $options['wm_position'] : 9;
        $color = $options['wm_text_color'] ? $this->hex_to_dec($options['wm_text_color']) : array(255, 255, 255);
        $font = $options['wm_text_font'] ? stripslashes_deep($options['wm_text_font']) : WPPAO_IMAGE_DIR. 'fonts/arial.ttf';
        $text = $options['wm_text_content'] ? stripslashes_deep($options['wm_text_content']) : get_bloginfo('url');

        $upload_dir = wp_upload_dir();
        $output_file = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wppimg/preview.jpg';

        if(file_exists($output_file)){
            unlink($output_file);
        }

        if ($options['wm_type'] == 'image') {
            $args = array(
                'dst_file' => $dst,
                'src_file' => $src,
                'alpha' => $alpha,
                'position' => $position,
                'output_file' => $output_file,
                'is_preview' => true
            );
            $this->image_water('', $args);
        } else {
            $args = array(
                'file' => $dst,
                'font' => $font,
                'size' => $size,
                'alpha' => $alpha,
                'text' => $text,
                'color' => $color,
                'position' => $position,
                'output_file' => $output_file,
                'is_preview' => true
            );
            $this->text_water('', $args);
        }

        return $upload_dir['baseurl'] . DIRECTORY_SEPARATOR . 'wppimg/preview.jpg';;

    }


    /**
     * @return mixed
     * 默认字体 文件夹 fonts
     */
    static function default_fonts(){
        $font_dir = WPPAO_IMAGE_DIR.'fonts/';
        $font_names = scandir( $font_dir );
        unset( $font_names[0] );
        unset( $font_names[1] );
        foreach( $font_names as $font_name ){
            $fonts[$font_name] = $font_dir.$font_name;
        }
        return $fonts;
    }

    /**
     * @return mixed
     * 自选字体 上传到uploads目录下 wppimg/fonts/下
     */
    static function custom_fonts(){
        $uploads = wp_upload_dir();
        $font_dir = $uploads['basedir'].'/wppimg/fonts/';
        if( is_dir( $font_dir ) ){
            $font_names = scandir( $font_dir );
            unset( $font_names[0] );
            unset( $font_names[1] );
            foreach( $font_names as $font_name ){
                $fonts[$font_name] = $font_dir.$font_name;
            }
            return $fonts;
        }
    }

}