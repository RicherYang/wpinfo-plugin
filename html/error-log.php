<div class="wrap">
    <h1>擷取錯誤紀錄</h1>

    <?php $list_table->views();?>
    <form method="get">
        <?php foreach ($list_table->get_args as $name => $value) { ?>
        <input type="hidden" name="<?=esc_attr($name) ?>" value="<?=esc_attr($value) ?>">
        <?php } ?>

        <?php $list_table->display(); ?>
    </form>
</div>
