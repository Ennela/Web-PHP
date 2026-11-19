<div id="pagination">
<?php
if ($current_page > 3) {
    $first_page = 1;
    ?>
    <a class="page-item" href="?per_page=<?= (int)$item_per_page ?>&amp;page=<?= (int)$first_page ?>">Đầu tiên</a>
    <?php
}
if ($current_page > 1) {
    $prev_page = $current_page - 1;
    ?>
    <a class="page-item" href="?per_page=<?= (int)$item_per_page ?>&amp;page=<?= (int)$prev_page ?>">Trước đó</a>
<?php }
?>
<?php for ($num = 1; $num <= $totalPages; $num++) { ?>
    <?php if ($num != $current_page) { ?>
        <?php if ($num > $current_page - 3 && $num < $current_page + 3) { ?>
            <a class="page-item" href="?per_page=<?= (int)$item_per_page ?>&amp;page=<?= (int)$num ?>"><?= (int)$num ?></a>
        <?php } ?>
    <?php } else { ?>
        <strong class="current-page page-item"><?= (int)$num ?></strong>
    <?php } ?>
<?php } ?>
<?php
if ($current_page < $totalPages - 1) {
    $next_page = $current_page + 1;
    ?>
    <a class="page-item" href="?per_page=<?= (int)$item_per_page ?>&amp;page=<?= (int)$next_page ?>">Kế tiếp</a>
<?php
}
if ($current_page < $totalPages - 3) {
    $end_page = $totalPages;
    ?>
    <a class="page-item" href="?per_page=<?= (int)$item_per_page ?>&amp;page=<?= (int)$end_page ?>">Cuối cùng</a>
    <?php
}
?>
</div>