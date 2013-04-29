<?php 

echo Form::open(array_merge($form, array('class' => 'form-vertical')));

if ($token) echo Form::token();

foreach ($hiddens as $hidden) echo $hidden;

foreach ($fieldsets as $fieldset) { ?>

	<fieldset<?php echo Html::attributes($fieldset->attributes ?: array()); ?>>
		
		<?php if( $fieldset->name ) : ?><legend><?php echo $fieldset->name ?: '' ?></legend><?php endif; ?>

		<?php foreach ($fieldset->controls() as $control) { ?>

		<div class="control-group<?php echo $errors->has($control->name) ? ' error' : '' ?>">
			<?php echo Form::label($control->name, $control->label, array('class' => 'control-label')); ?>
			
			<div class="controls">
				<?php echo call_user_func($control->field, $row, $control, array()); ?>
				<?php if( $control->inlineHelp ) : ?><span class="help-inline"><?php echo $control->inlineHelp; ?></span><?php endif; ?>
				<?php if( $control->help ) : ?><p class="help-block"><?php echo $control->help; ?></p><?php endif; ?>
				<?php echo $errors->first($control->name, $errorMessage); ?>
			</div>
		</div>

		<?php } ?>
	
	</fieldset>
<?php } ?>

<div class="form-actions">
	<button type="submit" class="btn btn-primary"><?php echo $submitButton; ?></button>
</div>

<?php echo Form::close(); ?>