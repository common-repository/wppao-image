<?php

    function wppimg_setting_helper(){
        if(isset($_POST['preview_the_watermark']) && stripslashes_deep($_POST['preview_the_watermark'])){
            $wppimg_get_preview = new WPPAO_Watermark();
            $preview_image = $wppimg_get_preview->get_preview_with_watermark();
        }

        $result = '<div class="wrap">
                <h1>水印浏览:</h1>
                <h2>说明</h2>
                <p>如果设置不对，生成不会成功！</p>
                <h2>水印预览图</h2>';
        if(isset($preview_image) && stripslashes_deep($preview_image)){
            $result .= '<div class="preview_image"><img src="'.$preview_image.'?tempid='.strtotime(date("H:i:s",time())).'"></div>';
        }else{
            $result .= '<div class="preview_not">暂未生成</div>';
        }
        $result .= '<h2>生成水印预览图</h2>
                <form action="" method="post" name="preview_new_watermark" id="preview_new_watermark" class="validate">
                     <p class="submit"><input type="submit" name="preview_the_watermark" id="preview_the_watermark" class="button button-primary" value="生成预览图"></p>
                </form>
                </div>
                ';

        echo $result;
    }
