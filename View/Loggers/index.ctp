<div class="loggers index">
	<h2><?php echo __d('auditable', 'Logs'); ?></h2>

	<p><?php echo $this->Paginator->counter(); ?></p>

	<table cellpadding="0" cellspacing="0">
	<tr>
		<th><?php echo $this->Paginator->sort('id'); ?></th>
		<th><?php echo $this->Paginator->sort('type'); ?></th>
		<th><?php echo $this->Paginator->sort('created'); ?></th>
		<th class="actions"><?php echo __d('auditable', 'Actions'); ?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($loggers as $logger):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
		?>
		<tr<?php echo $class; ?>>
			<td><?php echo $logger['Logger']['id']; ?></td>
			<td><?php echo $this->Auditor->type($logger['Logger']['type']); ?></td>
			<td><?php echo $logger['Logger']['created']; ?></td>
			<td class="actions">
				<?php echo $this->Html->link(__d('auditable', 'View'), array('action' => 'view', $logger['Logger']['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
	<div class="paging">
		<?php
		echo $this->Paginator->prev('< ' . __('previous'), array(), null, array('class' => 'prev disabled'));
		echo $this->Paginator->numbers(array('separator' => ''));
		echo $this->Paginator->next(__('next') . ' >', array(), null, array('class' => 'next disabled'));
		?>
	</div>
</div>