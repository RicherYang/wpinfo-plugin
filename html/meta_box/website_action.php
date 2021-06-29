<p>
    <span class="spinner"></span><button type="button" class="button wpi_ajax" id="get_website_info">取得網站資訊</button>
</p>
<p>
    <span class="spinner"></span><button type="button" class="button wpi_ajax" id="get_website_category">取得分類列表</button>
</p>

<?php if ($post->post_status != 'abandoned') { ?>
<p>
    <a href="<?=esc_url($abandoned_link) ?>">標註廢站</a>
</p>
<?php }
