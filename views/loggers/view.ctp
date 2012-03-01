<div class="loggers view">
<h2><?php echo __d('auditable', 'Log', true); ?></h2>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class; ?>><?php echo __d('auditable', 'Model', true); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class; ?>>
			<?php echo $logger['Logger']['model_alias']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class; ?>><?php echo __d('auditable', 'Model ID', true); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class; ?>>
			<?php echo $logger['Logger']['model_id']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class; ?>><?php echo __d('auditable', 'Difference', true); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class; ?>>
			<?php echo $this->Auditor->format($logger['LogDetail']['difference'], $logger['Logger']['type']); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class; ?>><?php echo __d('auditable', 'Created', true); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class; ?>>
			<?php echo $logger['Logger']['created']; ?>
			&nbsp;
		</dd>
	</dl>
</div>