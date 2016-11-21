<?php
/**
 * @file
 * Template for yild related block.
 */
?>
<ul class="yild_related">
<?php foreach ($items as $i): ?>
  <li><time class="time"><?php print (format_date($i->created, 'short')); ?></time> - <?php print l($i->title, 'node/' . $i->nid); ?> <span class="amount">(<?php print $i->amt; ?>)</span></li>
<?php endforeach; ?>
</ul>
